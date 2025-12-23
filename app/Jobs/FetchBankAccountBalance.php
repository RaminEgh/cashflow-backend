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
    public function __construct(public Deposit $deposit)
    {
        Log::info("Starting to fetch balance for deposit ID: {$this->deposit->number}");
    }

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

        Log::info("Starting to fetch balances for deposit ID: {$this->deposit->number}");

        $balance = null;
        $balanceStatus = BalanceStatus::Fail;
        $rahkaranBalance = null;
        $rahkaranFetchedAt = null;
        $rahkaranStatus = BalanceStatus::Fail;

        // Fetch bank balance
        if ($this->shouldFetchBalanceFromBankApi()) {
            try {
                $adapter = $bankFactory->make($this->deposit->bank->slug);
                $balance = $adapter->setAccount([
                    'accountNumber' => $this->deposit->number,
                    'organSlug' => $this->deposit->organ->slug,
                ])->getBalance();
                $balanceStatus = BalanceStatus::Success;

                Log::info("Successfully fetched bank balance for deposit ID: {$this->deposit->id} with balance: {$balance}");
            } catch (Throwable $e) {
                Log::error("Failed to fetch bank balance for deposit ID {$this->deposit->id}: " . $e->getMessage());
            }
        } else {
            Log::info("Skipping bank balance fetch (no access configured) for deposit ID: {$this->deposit->id}", [
                'bank' => $this->deposit->bank->slug,
            ]);
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
                    $rahkaranBalance = (int) $rahkaranData['balance'];
                    $rahkaranFetchedAt = Carbon::parse($rahkaranData['job_Date'])->toDateTimeString();
                    $rahkaranStatus = BalanceStatus::Success;

                    Log::info("Successfully fetched Rahkaran balance for deposit ID: {$this->deposit->id} with balance: {$rahkaranBalance}");
                } else {
                    Log::warning("Rahkaran response missing required fields for deposit ID: {$this->deposit->id}", [
                        'response' => $rahkaranData,
                    ]);
                }
            } else {
                Log::error("Failed to fetch Rahkaran balance for deposit ID {$this->deposit->id}: HTTP {$response->status()}");
            }
        } catch (Throwable $e) {
            Log::error("Failed to fetch Rahkaran balance for deposit ID {$this->deposit->id}: " . $e->getMessage());
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
            $updateData['balance_last_synced_at'] = now();
        }

        if ($rahkaranBalance !== null && $rahkaranFetchedAt !== null) {
            $updateData['rahkaran_balance'] = $rahkaranBalance;
            $updateData['rahkaran_balance_last_synced_at'] = $rahkaranFetchedAt;
        }

        if (! empty($updateData)) {
            $this->deposit->update($updateData);
        }

        // If both failed, throw an exception to trigger retry
        if ($balanceStatus === BalanceStatus::Fail && $rahkaranStatus === BalanceStatus::Fail) {
            throw new \Exception("Failed to fetch both bank and Rahkaran balances for deposit ID: {$this->deposit->id}");
        }

        Log::info("Successfully updated balances for deposit ID: {$this->deposit->id}", [
            'bank_balance' => $balance,
            'rahkaran_balance' => $rahkaranBalance,
        ]);
    }

    private function shouldFetchBalanceFromBankApi(): bool
    {
        return (bool) $this->deposit->has_access_banking_api;
    }
}
