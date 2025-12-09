<?php

namespace App\Services\ParsianBank;

use App\Enums\BalanceStatus;
use App\Models\Balance;
use App\Models\Bank;
use App\Models\Deposit;
use App\Services\Banking\BankAdapterFactory;
use Illuminate\Support\Facades\Log;

class BalanceFetchService
{
    public function __construct(
        protected BankAdapterFactory $bankAdapterFactory
    ) {}

    /**
     * Fetch and store balances for all Parsian Bank deposits
     */
    public function fetchAndStore(): void
    {
        $parsianBank = Bank::where('en_name', 'parsian')->first();

        if (! $parsianBank) {
            Log::warning('Parsian Bank not found in database');

            return;
        }

        $deposits = Deposit::where('bank_id', $parsianBank->id)->get();

        if ($deposits->isEmpty()) {
            Log::info('No Parsian Bank deposits found');

            return;
        }

        Log::info("Found {$deposits->count()} Parsian Bank deposits to fetch balances for");

        foreach ($deposits as $deposit) {
            try {
                $this->fetchAndStoreForDeposit($deposit);
            } catch (\Throwable $e) {
                Log::error("Error fetching/storing balance for deposit {$deposit->number}: ".$e->getMessage(), [
                    'deposit_id' => $deposit->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                continue;
            }
        }
    }

    /**
     * Fetch and store balance for a specific deposit
     */
    public function fetchAndStoreForDeposit(Deposit $deposit): void
    {
        try {
            $adapter = $this->bankAdapterFactory->make('parsian');
            $balanceData = $adapter->setAccount([
                'accountNumber' => $deposit->number,
            ])->getAccountBalance();

            $balance = (int) $balanceData['balance'];
            $fetchedAt = now();

            // Check if balance already exists for today
            $exists = Balance::where('deposit_id', $deposit->id)
                ->whereDate('fetched_at', $fetchedAt->toDateString())
                ->exists();

            if ($exists) {
                Log::info("Balance already exists for deposit {$deposit->number} today, skipping");

                return;
            }

            // Create balance record
            Balance::create([
                'deposit_id' => $deposit->id,
                'fetched_at' => $fetchedAt,
                'status' => BalanceStatus::Success->value,
                'balance' => $balance,
            ]);

            // Update deposit with new balance
            $deposit->update([
                'balance' => $balance,
                'balance_last_synced_at' => $fetchedAt,
            ]);

            Log::info("Successfully fetched and stored balance for deposit {$deposit->number}", [
                'deposit_id' => $deposit->id,
                'balance' => $balance,
            ]);
        } catch (\Throwable $e) {
            // Create failed balance record
            Balance::create([
                'deposit_id' => $deposit->id,
                'fetched_at' => now(),
                'status' => BalanceStatus::Fail->value,
                'balance' => null,
            ]);

            Log::error("Failed to fetch balance for deposit {$deposit->number}", [
                'deposit_id' => $deposit->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
