<?php

namespace Database\Seeders;

use App\Models\Deposit;
use Illuminate\Database\Seeder;

class UpdateDepositsBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deposits = Deposit::all();

        if ($deposits->isEmpty()) {
            $this->command->warn('No deposits found.');

            return;
        }

        // Select 2 random deposits to have slight difference
        $depositsWithDifference = [];
        if ($deposits->count() >= 2) {
            $depositsWithDifference = $deposits->random(2)->pluck('id')->toArray();
        } else {
            $depositsWithDifference = $deposits->pluck('id')->toArray();
        }

        $this->command->info('Updating deposits balances...');
        $this->command->line('Deposits with difference: '.implode(', ', $depositsWithDifference));

        $updated = 0;

        foreach ($deposits as $deposit) {
            $hasDifference = in_array($deposit->id, $depositsWithDifference);

            // If deposit has rahkaran_balance, use it as base, otherwise generate a random value
            if ($deposit->rahkaran_balance) {
                $rahkaranBalance = $deposit->rahkaran_balance;
            } else {
                // Generate a random balance if rahkaran_balance doesn't exist
                $rahkaranBalance = fake()->numberBetween(10_000_000, 1_000_000_000_000);
            }

            if ($hasDifference) {
                // For 2 random deposits, add slight difference (1% to 3% difference)
                $differencePercent = fake()->randomFloat(2, 0.01, 0.03);
                $difference = (int) ($rahkaranBalance * $differencePercent);
                $sign = fake()->boolean() ? 1 : -1;

                $balance = $rahkaranBalance + ($sign * $difference);
                $balance = max(0, $balance); // Ensure non-negative
            } else {
                // For other deposits, balance equals rahkaran_balance
                $balance = $rahkaranBalance;
            }

            $deposit->update([
                'balance' => $balance,
                'rahkaran_balance' => $rahkaranBalance,
                'balance_last_synced_at' => now(),
                'rahkaran_balance_last_synced_at' => now(),
            ]);

            $updated++;

            $this->command->line("  Updated deposit ID: {$deposit->id} - Balance: {$balance}, Rahkaran Balance: {$rahkaranBalance}");
        }

        $this->command->info("Total deposits updated: {$updated}");
    }
}
