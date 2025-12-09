<?php

namespace Database\Seeders;

use App\Enums\BalanceStatus;
use App\Models\Balance;
use App\Models\Deposit;
use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;
use Illuminate\Database\Seeder;

class BalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deposits = Deposit::all();

        if ($deposits->isEmpty()) {
            $this->command->warn('No deposits found. Please seed deposits first.');

            return;
        }

        // Get current Persian year
        $currentJalaliYear = Verta::now()->year;

        // First day of Farvardin (1/1) of current year - this is the start date
        $firstFarvardin = Verta::parse(sprintf('%04d/%02d/%02d', $currentJalaliYear, 1, 1));
        $startDate = Carbon::parse($firstFarvardin->datetime()->format('Y-m-d'));

        // Yesterday - this is the end date
        $endDate = Carbon::yesterday();

        // Ensure start date is before or equal to end date
        if ($startDate->gt($endDate)) {
            $this->command->warn('First Farvardin is after yesterday. Using yesterday as both start and end date.');
            $startDate = $endDate->copy();
        }

        $this->command->info("Seeding balances from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        $totalCreated = 0;

        foreach ($deposits as $deposit) {
            $this->command->line("Processing deposit ID: {$deposit->id} (Organ: {$deposit->organ_id})");

            // Generate a base balance amount for this deposit (to make it realistic)
            $baseBalance = fake()->numberBetween(10_000_000, 1_000_000_000_000);

            // Select 4 random days to have different balance values
            $daysCount = $startDate->diffInDays($endDate) + 1;
            $randomDays = [];
            if ($daysCount >= 4) {
                $randomDays = fake()->randomElements(
                    range(0, $daysCount - 1),
                    min(4, $daysCount)
                );
            } else {
                $randomDays = range(0, $daysCount - 1);
            }

            $currentDate = $startDate->copy();
            $dayIndex = 0;
            $balancesToInsert = [];

            while ($currentDate->lte($endDate)) {
                // Check if this day should have different values
                $hasDifference = in_array($dayIndex, $randomDays);

                // Generate balance values
                if ($hasDifference) {
                    // For random days, add some difference (between 1% to 5% difference)
                    $differencePercent = fake()->randomFloat(2, 0.01, 0.05);
                    $difference = (int) ($baseBalance * $differencePercent);
                    $sign = fake()->boolean() ? 1 : -1;

                    $balance = $baseBalance;
                    $rahkaranBalance = $baseBalance + ($sign * $difference);
                } else {
                    // Same values for most days
                    $balance = $baseBalance;
                    $rahkaranBalance = $baseBalance;
                }

                // Slightly vary the base balance for next day (realistic fluctuation)
                $baseBalance += fake()->numberBetween(-10_000_000, 10_000_000);
                $baseBalance = max(0, $baseBalance); // Ensure non-negative

                $balancesToInsert[] = [
                    'deposit_id' => $deposit->id,
                    'fetched_at' => $currentDate->copy()->setTime(12, 0, 0),
                    'rahkaran_fetched_at' => $currentDate->copy()->setTime(12, 0, 0),
                    'status' => BalanceStatus::Success->value,
                    'rahkaran_status' => BalanceStatus::Success->value,
                    'balance' => $balance,
                    'rahkaran_balance' => $rahkaranBalance,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $currentDate->addDay();
                $dayIndex++;
            }

            // Insert in batches for better performance
            $chunks = array_chunk($balancesToInsert, 500);
            foreach ($chunks as $chunk) {
                Balance::insert($chunk);
                $totalCreated += count($chunk);
            }

            $this->command->line('  Created ' . count($balancesToInsert) . " balance records for deposit ID: {$deposit->id}");
        }

        $this->command->info("Total balance records created: {$totalCreated}");
    }
}
