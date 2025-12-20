<?php

namespace App\Console\Commands;

use App\Models\Deposit;
use App\Models\Organ;
use App\Services\Rahkaran\IncomeOutgoingService;
use Illuminate\Console\Command;

class CalculateMonthlyIncomeExpenseCommand extends Command
{
    protected $signature = 'app:calculate-monthly-income-expense
                            {deposit_id? : The deposit ID to calculate for}
                            {year_month? : The year-month in YYYY-MM format}
                            {--all : Calculate for all deposits}
                            {--yearly : Calculate yearly summary}
                            {--organ= : Calculate for all deposits of an organ (organ ID)}
                            {--all-organs : Calculate for all organs}';

    protected $description = 'Calculate monthly income and expenses for rahkaran accounts';

    public function __construct(
        private IncomeOutgoingService $monthlyIncomeExpenseService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $depositId = $this->argument('deposit_id');
        $yearMonth = $this->argument('year_month');
        $all = $this->option('all');
        $yearly = $this->option('yearly');
        $organId = $this->option('organ');
        $allOrgans = $this->option('all-organs');

        if ($allOrgans) {
            $this->calculateForAllOrgans($yearMonth);
        } elseif ($organId && $yearly) {
            $this->calculateOrganYearlySummary($organId, $yearMonth);
        } elseif ($organId) {
            $this->calculateForOrgan($organId, $yearMonth);
        } elseif ($all) {
            $this->calculateForAllDeposits($yearMonth);
        } elseif ($depositId && $yearly) {
            $this->calculateYearlySummary($depositId, $yearMonth);
        } elseif ($depositId && $yearMonth) {
            $this->calculateForSingleDeposit($depositId, $yearMonth);
        } else {
            $this->showAvailableOptions();
        }
    }

    private function calculateForSingleDeposit(int $depositId, string $yearMonth)
    {
        $deposit = Deposit::find($depositId);
        if (! $deposit) {
            $this->error("Deposit with ID {$depositId} not found.");

            return;
        }

        $this->info("Calculating monthly income/expense for deposit: {$deposit->number}");

        $result = $this->monthlyIncomeExpenseService->calculateMonthlyIncomeExpense($depositId, $yearMonth);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Year-Month', $result['year_month']],
                ['Start Balance', number_format($result['start_balance'] ?? 0)],
                ['End Balance', number_format($result['end_balance'] ?? 0)],
                ['Income', number_format($result['income'])],
                ['Expenses', number_format($result['expenses'])],
                ['Net Change', number_format($result['net_change'])],
                ['Balance Records', $result['balance_records_count']],
            ]
        );
    }

    private function calculateForOrgan(int $organId, string $yearMonth)
    {
        $organ = Organ::find($organId);
        if (! $organ) {
            $this->error("Organ with ID {$organId} not found.");

            return;
        }

        $this->info("Calculating monthly income/expense for organ: {$organ->name}");

        $result = $this->monthlyIncomeExpenseService->calculateOrganMonthlyIncomeExpense($organId, $yearMonth);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Year-Month', $result['year_month']],
                ['Organ', $result['organ']['name']],
                ['Total Income', number_format($result['total_income'])],
                ['Total Expenses', number_format($result['total_expenses'])],
                ['Total Net Change', number_format($result['total_net_change'])],
                ['Deposits Count', $result['deposits_count']],
            ]
        );

        if ($result['deposits_data']->count() > 0) {
            $this->info("\nDeposits breakdown:");
            $tableData = [];
            foreach ($result['deposits_data'] as $depositData) {
                $deposit = $depositData['deposit'];
                $tableData[] = [
                    $deposit->id,
                    $deposit->number,
                    number_format($depositData['start_balance'] ?? 0),
                    number_format($depositData['end_balance'] ?? 0),
                    number_format($depositData['income']),
                    number_format($depositData['expenses']),
                    number_format($depositData['net_change']),
                ];
            }

            $this->table(
                ['ID', 'Account', 'Start Balance', 'End Balance', 'Income', 'Expenses', 'Net Change'],
                $tableData
            );
        }
    }

    private function calculateForAllDeposits(string $yearMonth)
    {
        $this->info("Calculating monthly income/expense for all deposits in {$yearMonth}");

        $results = $this->monthlyIncomeExpenseService->calculateAllDepositsMonthlyIncomeExpense($yearMonth);

        $tableData = [];
        foreach ($results as $result) {
            $deposit = $result['deposit'];
            $tableData[] = [
                $deposit->id,
                $deposit->number,
                $deposit->organ->name ?? 'N/A',
                number_format($result['start_balance'] ?? 0),
                number_format($result['end_balance'] ?? 0),
                number_format($result['income']),
                number_format($result['expenses']),
                number_format($result['net_change']),
            ];
        }

        $this->table(
            ['ID', 'Account', 'Organ', 'Start Balance', 'End Balance', 'Income', 'Expenses', 'Net Change'],
            $tableData
        );
    }

    private function calculateForAllOrgans(string $yearMonth)
    {
        $this->info("Calculating monthly income/expense for all organs in {$yearMonth}");

        $results = $this->monthlyIncomeExpenseService->calculateAllOrgansMonthlyIncomeExpense($yearMonth);

        $tableData = [];
        foreach ($results as $result) {
            $organ = $result['organ'];
            $tableData[] = [
                $organ->id,
                $organ->name,
                number_format($result['total_income']),
                number_format($result['total_expenses']),
                number_format($result['total_net_change']),
                $result['deposits_count'],
            ];
        }

        $this->table(
            ['ID', 'Organ', 'Total Income', 'Total Expenses', 'Total Net Change', 'Deposits Count'],
            $tableData
        );
    }

    private function calculateYearlySummary(int $depositId, string $yearMonth)
    {
        $deposit = Deposit::find($depositId);
        if (! $deposit) {
            $this->error("Deposit with ID {$depositId} not found.");

            return;
        }

        $year = substr($yearMonth, 0, 4);
        $this->info("Calculating yearly summary for deposit: {$deposit->number} in year {$year}");

        $results = $this->monthlyIncomeExpenseService->getYearlySummary($depositId, (int) $year);

        $tableData = [];
        foreach ($results as $result) {
            $tableData[] = [
                $result['year_month'],
                number_format($result['start_balance'] ?? 0),
                number_format($result['end_balance'] ?? 0),
                number_format($result['income']),
                number_format($result['expenses']),
                number_format($result['net_change']),
            ];
        }

        $this->table(
            ['Month', 'Start Balance', 'End Balance', 'Income', 'Expenses', 'Net Change'],
            $tableData
        );

        $totalIncome = $results->sum('income');
        $totalExpenses = $results->sum('expenses');
        $totalNetChange = $results->sum('net_change');

        $this->info("\nYearly Totals:");
        $this->info('Total Income: '.number_format($totalIncome));
        $this->info('Total Expenses: '.number_format($totalExpenses));
        $this->info('Net Change: '.number_format($totalNetChange));
    }

    private function calculateOrganYearlySummary(int $organId, string $yearMonth)
    {
        $organ = Organ::find($organId);
        if (! $organ) {
            $this->error("Organ with ID {$organId} not found.");

            return;
        }

        $year = substr($yearMonth, 0, 4);
        $this->info("Calculating yearly summary for organ: {$organ->name} in year {$year}");

        $result = $this->monthlyIncomeExpenseService->getOrganYearlySummary($organId, (int) $year);

        $tableData = [];
        foreach ($result['monthly_data'] as $monthlyResult) {
            $tableData[] = [
                $monthlyResult['year_month'],
                number_format($monthlyResult['total_income']),
                number_format($monthlyResult['total_expenses']),
                number_format($monthlyResult['total_net_change']),
                $monthlyResult['deposits_count'],
            ];
        }

        $this->table(
            ['Month', 'Total Income', 'Total Expenses', 'Total Net Change', 'Deposits Count'],
            $tableData
        );

        $yearlyTotals = $result['yearly_totals'];
        $this->info("\nYearly Totals:");
        $this->info('Total Income: '.number_format($yearlyTotals['total_income']));
        $this->info('Total Expenses: '.number_format($yearlyTotals['total_expenses']));
        $this->info('Total Net Change: '.number_format($yearlyTotals['total_net_change']));
    }

    private function showAvailableOptions()
    {
        $this->info('Available commands:');
        $this->info('1. Calculate for specific deposit: php artisan app:calculate-monthly-income-expense {deposit_id} {year_month}');
        $this->info('2. Calculate for all deposits: php artisan app:calculate-monthly-income-expense --all {year_month}');
        $this->info('3. Calculate yearly summary: php artisan app:calculate-monthly-income-expense {deposit_id} {year_month} --yearly');
        $this->info('4. Calculate for organ: php artisan app:calculate-monthly-income-expense --organ={organ_id} {year_month}');
        $this->info('5. Calculate organ yearly summary: php artisan app:calculate-monthly-income-expense --organ={organ_id} {year_month} --yearly');
        $this->info('6. Calculate for all organs: php artisan app:calculate-monthly-income-expense --all-organs {year_month}');

        $this->info("\nAvailable deposits:");
        $deposits = Deposit::with('organ')->get();
        $tableData = [];
        foreach ($deposits as $deposit) {
            $tableData[] = [
                $deposit->id,
                $deposit->number,
                $deposit->organ->name ?? 'N/A',
                $deposit->bank->name ?? 'N/A',
            ];
        }

        $this->table(
            ['ID', 'Account Number', 'Organ', 'Bank'],
            $tableData
        );

        $this->info("\nAvailable organs:");
        $organs = Organ::with('deposits')->get();
        $tableData = [];
        foreach ($organs as $organ) {
            $tableData[] = [
                $organ->id,
                $organ->name,
                $organ->deposits->count(),
            ];
        }

        $this->table(
            ['ID', 'Organ Name', 'Deposits Count'],
            $tableData
        );
    }
}
