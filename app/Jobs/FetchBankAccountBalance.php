<?php

namespace App\Jobs;

use App\Models\Deposit;
use App\Services\Banking\BankAdapterFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
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
     * Execute the job.
     */
    public function handle(BankAdapterFactory $bankFactory): void
    {
        try {
            Log::info("Starting to fetch balance for deposit ID: {$this->deposit->branch_code}");

            $adapter = $bankFactory->make($this->deposit->bank->en_name);
            $balance = $adapter->setAccount([
                'number' => $this->deposit->number
            ])->getBalance();
            // Call mock API to get bank balance
            // $balance = $this->fetchBalanceFromMockApi();

            // Create balance log
            $this->deposit->balances()->create([
                'balance' => $balance,
                'fetched_at' => now(),
                'status' => 'success'
            ]);

            // Update deposit with new balance
            $this->deposit->update([
                'balance' => $balance,
                'balance_last_synced_at' => now()
            ]);

            Log::info("Successfully updated balance for deposit ID: {$this->deposit->id} with balance: {$balance}");

        } catch (Throwable $e) {
            Log::error("Failed to fetch balance for deposit ID {$this->deposit->id}: " . $e->getMessage());

            // Create failed balance log
            $this->deposit->balances()->create([
                'balance' => 0,
                'fetched_at' => now(),
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            // This will cause the job to fail and be retried if $tries > 1
            $this->fail($e);
        }
    }

    /**
     * Mock API call to fetch bank balance
     */
    private function fetchBalanceFromMockApi(): float
    {
        try {
            // Simulate API call delay
            sleep(1);

            // Mock API response data
            $mockResponse = [
                'success' => true,
                'data' => [
                    'account_number' => $this->deposit->account_number ?? '1234567890',
                    'balance' => rand(10_000_000, 50_000_000),
                    'currency' => 'IRR',
                    'last_updated' => now()->toISOString(),
                    'status' => 'active',
                    'account_type' => 'checking',
                    'available_balance' => rand(800, 45000),
                    'pending_transactions' => rand(0, 5),
                ]
            ];

            Log::info("Mock API response received", [
                'deposit_id' => $this->deposit->id,
                'balance' => $mockResponse['data']['balance']
            ]);

            return (float) $mockResponse['data']['balance'];

        } catch (\Exception $e) {
            Log::error("Mock API call failed", [
                'deposit_id' => $this->deposit->id,
                'error' => $e->getMessage()
            ]);

            throw new \Exception("Failed to fetch balance from mock API: " . $e->getMessage());
        }
    }

    /**
     * Alternative method using HTTP client (if you want to call a real mock endpoint)
     */
    private function fetchBalanceFromHttpApi(): float
    {
        try {
            $response = Http::timeout(30)->get('http://localhost:8000/api/mock/bank/balance', [
                'account_number' => $this->deposit->account_number,
                'bank_name' => $this->deposit->bank->name ?? 'Mock Bank'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return (float) $data['data']['balance'];
            }

            throw new \Exception("API request failed with status: " . $response->status());

        } catch (\Exception $e) {
            Log::error("HTTP API call failed", [
                'deposit_id' => $this->deposit->id,
                'error' => $e->getMessage()
            ]);

            throw new \Exception("Failed to fetch balance from HTTP API: " . $e->getMessage());
        }
    }
}
