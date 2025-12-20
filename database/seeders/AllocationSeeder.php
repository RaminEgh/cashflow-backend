<?php

namespace Database\Seeders;

use App\Models\Allocation;
use App\Models\Organ;
use App\Models\User;
use Illuminate\Database\Seeder;

class AllocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organs = Organ::all();

        if ($organs->isEmpty()) {
            $this->command->warn('No organs found. Please seed organs first.');

            return;
        }

        // Get first admin user for created_by and updated_by
        $adminUser = User::where('type', User::TYPE_ADMIN)->first();
        if (! $adminUser) {
            $this->command->warn('No admin user found. Using user ID 1 as fallback.');
            $adminUserId = 1;
        } else {
            $adminUserId = $adminUser->id;
        }

        $years = [1404, 1405];
        $minAmount = 5_000_000_000; // 5 billion minimum
        $totalCreated = 0;

        foreach ($organs as $organ) {
            $this->command->line("Processing organ ID: {$organ->id} ({$organ->name})");

            foreach ($years as $year) {
                // Check if allocation already exists for this organ and year
                $existing = Allocation::where('organ_id', $organ->id)
                    ->where('year', $year)
                    ->exists();

                if ($existing) {
                    $this->command->line("  Allocation for year {$year} already exists, skipping...");

                    continue;
                }

                // Generate base budget for the year (between 5 billion and 500 billion)
                $baseYearlyBudget = fake()->numberBetween($minAmount, 500_000_000_000);

                // Generate monthly budgets (distribute yearly budget across months with some variation)
                // Each month gets approximately 1/12 of yearly budget with 10-20% variation
                $monthlyBudgets = [];
                $totalAllocated = 0;

                for ($month = 1; $month <= 12; $month++) {
                    if ($month === 12) {
                        // Last month gets remaining budget to ensure total equals yearly budget
                        $remaining = $baseYearlyBudget - $totalAllocated;
                        $monthlyBudgets[$month] = max($minAmount / 12, $remaining); // Ensure minimum
                    } else {
                        // Each month gets base amount (1/12) with ±10-20% variation
                        $baseMonthly = $baseYearlyBudget / 12;
                        $variation = fake()->randomFloat(2, -0.20, 0.20);
                        $monthBudget = (int) ($baseMonthly * (1 + $variation));
                        $monthBudget = max($minAmount / 12, $monthBudget); // Ensure minimum per month
                        $monthlyBudgets[$month] = $monthBudget;
                        $totalAllocated += $monthBudget;
                    }
                }

                // Generate monthly expenses (60-90% of budget for each month)
                $monthlyExpenses = [];
                foreach ($monthlyBudgets as $month => $budget) {
                    $expensePercent = fake()->randomFloat(2, 0.60, 0.90);
                    $expense = (int) ($budget * $expensePercent);
                    $monthlyExpenses[$month] = max(0, $expense);
                }

                // Create allocation
                $allocation = Allocation::create([
                    'organ_id' => $organ->id,
                    'year' => $year,
                    'description' => "بودجه و هزینه‌های تخصیصی سال {$year}",
                    'month_1_budget' => $monthlyBudgets[1],
                    'month_2_budget' => $monthlyBudgets[2],
                    'month_3_budget' => $monthlyBudgets[3],
                    'month_4_budget' => $monthlyBudgets[4],
                    'month_5_budget' => $monthlyBudgets[5],
                    'month_6_budget' => $monthlyBudgets[6],
                    'month_7_budget' => $monthlyBudgets[7],
                    'month_8_budget' => $monthlyBudgets[8],
                    'month_9_budget' => $monthlyBudgets[9],
                    'month_10_budget' => $monthlyBudgets[10],
                    'month_11_budget' => $monthlyBudgets[11],
                    'month_12_budget' => $monthlyBudgets[12],
                    'month_1_expense' => $monthlyExpenses[1],
                    'month_2_expense' => $monthlyExpenses[2],
                    'month_3_expense' => $monthlyExpenses[3],
                    'month_4_expense' => $monthlyExpenses[4],
                    'month_5_expense' => $monthlyExpenses[5],
                    'month_6_expense' => $monthlyExpenses[6],
                    'month_7_expense' => $monthlyExpenses[7],
                    'month_8_expense' => $monthlyExpenses[8],
                    'month_9_expense' => $monthlyExpenses[9],
                    'month_10_expense' => $monthlyExpenses[10],
                    'month_11_expense' => $monthlyExpenses[11],
                    'month_12_expense' => $monthlyExpenses[12],
                    'created_by' => $adminUserId,
                    'updated_by' => $adminUserId,
                ]);

                $totalCreated++;
                $this->command->line("  Created allocation for year {$year} - Total Budget: ".number_format($baseYearlyBudget));
            }
        }

        $this->command->info("Total allocations created: {$totalCreated}");
    }
}
