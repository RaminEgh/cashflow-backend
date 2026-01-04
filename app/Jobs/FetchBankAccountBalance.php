<?php

namespace App\Jobs;

use App\Enums\BalanceStatus;
use App\Models\Deposit;
use App\Services\Banking\BankAdapterFactory;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchBankAccountBalance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(public Deposit $deposit) {}

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        $tags = [
            'balance-fetch',
            "deposit:{$this->deposit->number}",
        ];

        if ($this->deposit->bank_id) {
            $tags[] = "bank:{$this->deposit->bank->slug}";
        }

        if ($this->deposit->organ_id) {
            $tags[] = "organ:{$this->deposit->organ->slug}";
        }

        return $tags;
    }

    /**
     * Execute the job.
     */
    public function handle(BankAdapterFactory $bankFactory): void
    {
        // Reload the deposit and its relationships since they're not loaded after serialization
        $this->deposit->refresh();
        $this->deposit->load(['bank', 'organ']);

        if (! $this->deposit->bank) {
            throw new \Exception("Bank not found for deposit ID: {$this->deposit->id}");
        }

        if (! $this->deposit->organ) {
            throw new \Exception("Organ not found for deposit ID: {$this->deposit->id}");
        }


        $balance = null;
        $balanceStatus = BalanceStatus::Fail;
        $rahkaranBalance = null;
        $rahkaranFetchedAt = null;
        $rahkaranStatus = BalanceStatus::Fail;

        // Fetch bank balance
        if ($this->shouldFetchBalanceFromBankApi()) {
            try {

                $adapter = $bankFactory->make($this->deposit->bank->slug);
                $balanceData = $adapter->setAccount([
                    'accountNumber' => $this->deposit->number,
                    'organSlug' => $this->deposit->organ->slug,
                ])->getBalance();

                $rawBalance = $balanceData['balance'];

                if ($this->isValidBalance($rawBalance)) {
                    $balance = (int) $rawBalance;
                    $balanceStatus = BalanceStatus::Success;
                } else {
                    $balanceStatus = BalanceStatus::Fail;
                }
            } catch (Throwable $e) {
            }
        } else {
        }

        // Fetch Rahkaran balance
        try {
            $rahkaranApi = config('services.rahkaran.base_endpoint');

            if (! $rahkaranApi) {
                throw new \Exception('RAHKARAN_BASE_ENDPOINT is not set in .env file');
            }

            $rahkaranApi = rtrim($rahkaranApi, '/');
            $url = "$rahkaranApi/{$this->deposit->organ->slug}/{$this->deposit->number}";

            $response = Http::timeout(30)->get($url);

            if ($response->successful()) {
                $rahkaranData = $response->json();

                if (isset($rahkaranData['balance']) && isset($rahkaranData['job_Date'])) {
                    $rawRahkaranBalance = $rahkaranData['balance'];

                    if ($this->isValidBalance($rawRahkaranBalance)) {
                        $rahkaranBalance = (int) $rawRahkaranBalance;
                        $rahkaranFetchedAt = Carbon::parse($rahkaranData['job_Date'])->toDateTimeString();
                        $rahkaranStatus = BalanceStatus::Success;
                    } else {
                        $rahkaranStatus = BalanceStatus::Fail;
                    }
                } else {
                }
            } else {
            }
        } catch (Throwable $e) {
        }

        // Create balance record with both balances
        $this->deposit->balances()->create([
            'balance' => $balance,
            'fetched_at' => now(),
            'status' => $balanceStatus->value,
            'rahkaran_status' => $rahkaranStatus->value,
            'rahkaran_balance' => $rahkaranBalance,
            'rahkaran_fetched_at' => $rahkaranFetchedAt,
        ]);

        // Update deposit with both balances and sync times
        $updateData = [];

        if ($balance !== null) {
            $updateData['balance'] = $balance;
            $updateData['balance_synced_at'] = now();
            $updateData['last_balance_sync_success'] = true;
        } else {
            $updateData['last_balance_sync_success'] = false;
        }

        if ($rahkaranBalance !== null && $rahkaranFetchedAt !== null) {
            $updateData['rahkaran_balance'] = $rahkaranBalance;
            $updateData['rahkaran_synced_at'] = $rahkaranFetchedAt;
            $updateData['last_rahkaran_sync_success'] = true;
        } else {
            $updateData['last_rahkaran_sync_success'] = false;
        }

        if (! empty($updateData)) {
            $this->deposit->update($updateData);
        }

        // Log the result (success or failure)
        if ($balanceStatus === BalanceStatus::Fail && $rahkaranStatus === BalanceStatus::Fail) {
        } else {
        }
    }

    private function shouldFetchBalanceFromBankApi(): bool
    {
        return (bool) $this->deposit->has_access_banking_api;
    }

    /**
     * Validate if a balance value is valid (non-negative and not null).
     *
     * @param  mixed  $balance
     * @return bool
     */
    private function isValidBalance($balance): bool
    {
        if ($balance === null) {
            return false;
        }

        // Convert to integer for validation
        $intBalance = (int) $balance;

        // Check if negative (invalid for unsigned columns)
        // The database column is unsignedBigInteger, so negative values cannot be stored
        if ($intBalance < 0) {
            return false;
        }

        return true;
    }
}
