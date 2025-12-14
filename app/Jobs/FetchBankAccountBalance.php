<?php

namespace App\Jobs;

use App\Enums\BalanceStatus;
use App\Models\Deposit;
use App\Services\Banking\BankAdapterFactory;
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
        try {
            // Reload the deposit and its bank relationship since it's not loaded after serialization
            $this->deposit->refresh();
            $this->deposit->load('bank');

            if (! $this->deposit->bank) {
                throw new \Exception("Bank not found for deposit ID: {$this->deposit->id}");
            }

            Log::info("Starting to fetch balance for deposit ID: {$this->deposit->number}");

            $adapter = $bankFactory->make($this->deposit->bank->slug);
            $balance = $adapter->setAccount([
                'accountNumber' => $this->deposit->number,
            ])->getBalance();
            
            Log::info('Balance fetched from Parsian Bank', [
                'balance' => $balance,
            ]);
            // Call mock API to get bank balance
            // $balance = $this->fetchBalanceFromMockApi();

            // Create balance log
            $this->deposit->balances()->create([
                'balance' => $balance,
                'fetched_at' => now(),
                'status' => BalanceStatus::Success->value,
            ]);

            // Update deposit with new balance
            $this->deposit->update([
                'balance' => $balance,
                'balance_last_synced_at' => now(),
            ]);

            Log::info("Successfully updated balance for deposit ID: {$this->deposit->id} with balance: {$balance}");
        } catch (Throwable $e) {
            Log::error("Failed to fetch balance for deposit ID {$this->deposit->id}: " . $e->getMessage());

            // Create failed balance log
            $this->deposit->balances()->create([
                'balance' => 0,
                'fetched_at' => now(),
                'status' => BalanceStatus::Fail->value,
            ]);

            // This will cause the job to fail and be retried if $tries > 1
            $this->fail($e);
        }
    }
}
