<?php

namespace App\Services\Banking;

use App\Models\Balance;
use App\Models\Organ;
use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class IncomeOutgoingService
{
    /**
     * Calculate monthly income and expenses for a specific deposit
     *
     * @param int $depositId
     * @param string $yearMonth Format: '2024-01'
     * @return array
     * @throws \Exception
     */
    public function calculateMonthlyIncomeOutgoing(int $depositId, string $yearMonth): array
    {
        list($year, $month) = explode('/', $yearMonth);
        $month = (int)$month;
        $year = (int)$year;

        if ($month >= 1 && $month <= 6) {
            $lastDay = 31;
        } elseif ($month >= 7 && $month <= 11) {
            $lastDay = 30;
        } elseif ($month == 12) {
            $isLeapYear = Verta::isLeapYear($year);
            $lastDay = $isLeapYear ? 30 : 29;
        }

        $startOfMonthJalali = sprintf('%04d/%02d/%02d', $year, $month, '01');
        $endOfMonthJalali = sprintf('%04d/%02d/%02d', $year, $month, $lastDay);
        $startGregorian = Verta::parse($startOfMonthJalali)->datetime()->format('Y-m-d');
        $endGregorian = Verta::parse($endOfMonthJalali)->datetime()->format('Y-m-d');


        $startGregorian = Carbon::parse($startGregorian);
        $endGregorian = Carbon::parse($endGregorian);

        // Get all balance records for the month
        $monthlyBalances = $this->getMonthlyBalances($depositId, $startGregorian, $endGregorian);

        // If there are no balances within the month, return zeros
        if ($monthlyBalances->isEmpty()) {
            Log::info('No monthly balances found for period', [
                'deposit_id' => $depositId,
                'startDate' => $startGregorian->toDateTimeString(),
                'endDate'   => $endGregorian->toDateTimeString(),
            ]);

            return [
                'deposit_id' => $depositId,
                'year_month' => $yearMonth,
                'start_balance' => null,
                'end_balance' => null,
                'income' => 0,
                'outgoing' => 0,
                'net_change' => 0,
                'balance_records_count' => 0,
            ];
        }

        // Use the first and last records within the month as start and end
        $startBalance = $monthlyBalances->first()->balance;
        $endBalance = $monthlyBalances->last()->balance;

        // Calculate income and outgoing based only on records inside the month
        $income = $this->calculateIncome($startBalance, $endBalance, $monthlyBalances);
        $outgoing = $this->calculateOutgoing($startBalance, $endBalance, $monthlyBalances);

        return [
            'deposit_id' => $depositId,
            'year_month' => $yearMonth,
            'start_balance' => $startBalance,
            'end_balance' => $endBalance,
            'income' => $income,
            'outgoing' => $outgoing,
            'net_change' => $endBalance - $startBalance,
            'balance_records_count' => $monthlyBalances->count(),
        ];
    }

    /**
     * Calculate monthly income and outgoing for all deposits of an organ
     *
     * @param int $organId
     * @param string $yearMonth Format: '2024-01'
     * @return array
     */
    public function calculateOrganMonthlyIncomeOutgoing(Organ $organ, string $yearMonth): array
    {
        $deposits = $organ->deposits;
        $totalIncome = 0;
        $totalOutgoing = 0;
        $totalNetChange = 0;
        $depositsData = collect();

        foreach ($deposits as $deposit) {
            try {
                $result = $this->calculateMonthlyIncomeOutgoing($deposit->id, $yearMonth);
            } catch (\Throwable $e) {
                Log::error('calculateMonthlyIncomeOutgoing error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                continue;
            }
            Log::alert('$result');
            Log::info($result);

            $result['deposit'] = $deposit;

            $totalIncome += $result['income'];
            $totalOutgoing += $result['outgoing'];
            $totalNetChange += $result['net_change'];

            $depositsData->push($result);
        }

        return [
            'organ_id' => $organ->id,
            'year_month' => $yearMonth,
            'total_income' => $totalIncome,
            'total_outgoing' => $totalOutgoing,
            'total_net_change' => $totalNetChange,
            'deposits_count' => $deposits->count(),
            'deposits_data' => $depositsData,
        ];
    }

    /**
     * Calculate monthly income and expenses for all deposits
     *
     * @param string $yearMonth Format: '2024-01'
     * @return Collection
     */
    public function calculateAllDepositsMonthlyIncomeOutgoing(Organ $organ, string $yearMonth): Collection
    {
        $deposits = $organ->deposits;
        $results = collect();

        foreach ($deposits as $deposit) {
            $result = $this->calculateMonthlyIncomeOutgoing($deposit->id, $yearMonth);
            $result['deposit'] = $deposit;
            $results->push($result);
        }

        return $results;
    }

    /**
     * Calculate monthly income and expenses for all organs
     *
     * @param string $yearMonth Format: '2024-01'
     * @return Collection
     */
    public function calculateAllOrgansMonthlyIncomeOutgoing(string $yearMonth): Collection
    {
        $organs = Organ::all();
        $results = collect();

        foreach ($organs as $organ) {
            $result = $this->calculateOrganMonthlyIncomeOutgoing($organ->id, $yearMonth);
            $results->push($result);
        }

        return $results;
    }

    /**
     * Get yearly summary for all deposits of an organ
     *
     * @param int $organId
     * @param int $year
     * @return array
     */
    public function getOrganYearlySummary(int $organId, int $year): array
    {
        $organ = Organ::with('deposits')->find($organId);
        if (!$organ) {
            return [
                'organ_id' => $organId,
                'year' => $year,
                'error' => 'Organ not found',
                'monthly_data' => collect(),
                'yearly_totals' => [
                    'total_income' => 0,
                    'total_expenses' => 0,
                    'total_net_change' => 0,
                ],
            ];
        }

        $monthlyData = collect();
        $yearlyTotalIncome = 0;
        $yearlyTotalOutgoing = 0;
        $yearlyTotalNetChange = 0;

        for ($month = 1; $month <= 12; $month++) {
            $yearMonth = sprintf('%04d-%02d', $year, $month);
            $monthlyResult = $this->calculateOrganMonthlyIncomeOutgoing($organId, $yearMonth);

            $yearlyTotalIncome += $monthlyResult['total_income'];
            $yearlyTotalOutgoing += $monthlyResult['total_outgoing'];
            $yearlyTotalNetChange += $monthlyResult['total_net_change'];

            $monthlyData->push($monthlyResult);
        }

        return [
            'organ_id' => $organId,
            'organ' => $organ,
            'year' => $year,
            'monthly_data' => $monthlyData,
            'yearly_totals' => [
                'total_income' => $yearlyTotalIncome,
                'total_expenses' => $yearlyTotalOutgoing,
                'total_net_change' => $yearlyTotalNetChange,
            ],
        ];
    }

    /**
     * Get balance at a specific date (last balance before or on that date)
     *
     * @param int $depositId
     * @param Carbon $date
     * @return int|null
     */
    private function getBalanceAtDate(int $depositId, Carbon $date): ?int
    {
        $balance = Balance::where('deposit_id', $depositId)
            ->where('status', 'success')
            ->where('balance', '!=', null)
            ->where('fetched_at', '<=', $date)
            ->orderBy('fetched_at', 'desc')
            ->first();

        return $balance ? $balance->balance : null;
    }

    /**
     * Get all balance records for a specific month
     *
     * @param int $depositId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    private function getMonthlyBalances(int $depositId, Carbon $startDate, Carbon $endDate): Collection
    {
        return Balance::where('deposit_id', $depositId)
            ->where('status', 'success')
            ->where('balance', '!=', null)
            ->whereBetween('fetched_at', [$startDate, $endDate])
            ->orderBy('fetched_at', 'asc')
            ->get();
    }

    /**
     * Calculate income based on balance changes
     * Income = positive changes in balance
     *
     * @param int|null $startBalance
     * @param int|null $endBalance
     * @param Collection $monthlyBalances
     * @return int
     */
    private function calculateIncome(?int $startBalance, ?int $endBalance, Collection $monthlyBalances): int
    {
        if ($startBalance === null || $endBalance === null) {
            return 0;
        }

        $income = 0;
        $previousBalance = $startBalance;

        foreach ($monthlyBalances as $balance) {
            if ($balance->balance > $previousBalance) {
                $income += $balance->balance - $previousBalance;
            }
            $previousBalance = $balance->balance;
        }

        return $income;
    }

    /**
     * Calculate expenses based on balance changes
     * Expenses = negative changes in balance
     *
     * @param int|null $startBalance
     * @param int|null $endBalance
     * @param Collection $monthlyBalances
     * @return int
     */
    private function calculateOutgoing(?int $startBalance, ?int $endBalance, Collection $monthlyBalances): int
    {
        if ($startBalance === null || $endBalance === null) {
            return 0;
        }

        $expenses = 0;
        $previousBalance = $startBalance;

        foreach ($monthlyBalances as $balance) {
            if ($balance->balance < $previousBalance) {
                $expenses += $previousBalance - $balance->balance;
            }
            $previousBalance = $balance->balance;
        }

        return $expenses;
    }

    /**
     * Get monthly summary for a specific deposit
     *
     * @param int $depositId
     * @param int $year
     * @return Collection
     */
    public function getYearlySummary(int $depositId, int $year): Collection
    {
        $results = collect();

        for ($month = 1; $month <= 12; $month++) {
            $yearMonth = sprintf('%04d-%02d', $year, $month);
            $result = $this->calculateMonthlyIncomeOutgoing($depositId, $yearMonth);
            $results->push($result);
        }

        return $results;
    }

    /**
     * Get detailed balance changes for a specific month
     *
     * @param int $depositId
     * @param string $yearMonth Format: '2024-01'
     * @return array
     */
    public function getDetailedMonthlyChanges(int $depositId, string $yearMonth): array
    {
        $date = Carbon::createFromFormat('Y-m', $yearMonth);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $balances = $this->getMonthlyBalances($depositId, $startOfMonth, $endOfMonth);
        $changes = collect();

        $previousBalance = $this->getBalanceAtDate($depositId, $startOfMonth);

        foreach ($balances as $balance) {
            if ($previousBalance !== null) {
                $change = $balance->balance - $previousBalance;
                $changes->push([
                    'date' => $balance->fetched_at,
                    'balance' => $balance->balance,
                    'change' => $change,
                    'type' => $change > 0 ? 'income' : ($change < 0 ? 'expense' : 'no_change'),
                    'amount' => abs($change),
                ]);
            }
            $previousBalance = $balance->balance;
        }

        return [
            'deposit_id' => $depositId,
            'year_month' => $yearMonth,
            'start_balance' => $this->getBalanceAtDate($depositId, $startOfMonth),
            'end_balance' => $this->getBalanceAtDate($depositId, $endOfMonth),
            'changes' => $changes,
            'total_income' => $changes->where('type', 'income')->sum('amount'),
            'total_outgoing' => $changes->where('type', 'expense')->sum('amount'),
        ];
    }
}
