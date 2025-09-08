<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Organ;
use App\Services\Rahkaran\IncomeOutgoingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MonthlyIncomeExpenseController extends Controller
{
    public function __construct(
        private IncomeOutgoingService $monthlyIncomeExpenseService
    ) {}

    /**
     * Get monthly income and expenses for a specific deposit
     *
     * @param Request $request
     * @param int $depositId
     * @return JsonResponse
     */
    public function getMonthlyIncomeExpense(Request $request, int $depositId): JsonResponse
    {
        $request->validate([
            'year_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $yearMonth = $request->input('year_month');

        // Check if deposit exists
        $deposit = Deposit::find($depositId);
        if (!$deposit) {
            return Helper::errorResponse('Deposit not found', 404);
        }

        $result = $this->monthlyIncomeExpenseService->calculateMonthlyIncomeExpense($depositId, $yearMonth);
        $result['deposit'] = $deposit;

        return Helper::successResponse('Monthly income and expenses calculated successfully', $result);
    }

    /**
     * Get monthly income and expenses for all deposits of an organ
     *
     * @param Request $request
     * @param int $organId
     * @return JsonResponse
     */
    public function getOrganMonthlyIncomeExpense(Request $request, int $organId): JsonResponse
    {
        $request->validate([
            'year_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $yearMonth = $request->input('year_month');

        // Check if organ exists
        $organ = Organ::find($organId);
        if (!$organ) {
            return Helper::errorResponse('Organ not found', 404);
        }

        $result = $this->monthlyIncomeExpenseService->calculateOrganMonthlyIncomeExpense($organId, $yearMonth);

        return Helper::successResponse('Organ monthly income and expenses calculated successfully', $result);
    }

    /**
     * Get monthly income and expenses for all deposits
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllDepositsMonthlyIncomeExpense(Request $request): JsonResponse
    {
        $request->validate([
            'year_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $yearMonth = $request->input('year_month');
        $results = $this->monthlyIncomeExpenseService->calculateAllDepositsMonthlyIncomeExpense($yearMonth);

        return Helper::successResponse('Monthly income and expenses calculated successfully for all deposits', $results);
    }

    /**
     * Get monthly income and expenses for all organs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllOrgansMonthlyIncomeExpense(Request $request): JsonResponse
    {
        $request->validate([
            'year_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $yearMonth = $request->input('year_month');
        $results = $this->monthlyIncomeExpenseService->calculateAllOrgansMonthlyIncomeExpense($yearMonth);

        return Helper::successResponse('Monthly income and expenses calculated successfully for all organs', $results);
    }

    /**
     * Get yearly summary for a specific deposit
     *
     * @param Request $request
     * @param int $depositId
     * @return JsonResponse
     */
    public function getYearlySummary(Request $request, int $depositId): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $year = $request->input('year');

        // Check if deposit exists
        $deposit = Deposit::find($depositId);
        if (!$deposit) {
            return Helper::errorResponse('Deposit not found', 404);
        }

        $results = $this->monthlyIncomeExpenseService->getYearlySummary($depositId, $year);

        $summary = [
            'deposit' => $deposit,
            'year' => $year,
            'monthly_data' => $results,
            'yearly_totals' => [
                'total_income' => $results->sum('income'),
                'total_expenses' => $results->sum('expenses'),
                'net_change' => $results->sum('net_change'),
            ],
        ];

        return Helper::successResponse('Yearly summary calculated successfully', $summary);
    }

    /**
     * Get yearly summary for all deposits of an organ
     *
     * @param Request $request
     * @param int $organId
     * @return JsonResponse
     */
    public function getOrganYearlySummary(Request $request, int $organId): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $year = $request->input('year');

        // Check if organ exists
        $organ = Organ::find($organId);
        if (!$organ) {
            return Helper::errorResponse('Organ not found', 404);
        }

        $result = $this->monthlyIncomeExpenseService->getOrganYearlySummary($organId, $year);

        return Helper::successResponse('Organ yearly summary calculated successfully', $result);
    }

    /**
     * Get detailed monthly changes for a specific deposit
     *
     * @param Request $request
     * @param int $depositId
     * @return JsonResponse
     */
    public function getDetailedMonthlyChanges(Request $request, int $depositId): JsonResponse
    {
        $request->validate([
            'year_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $yearMonth = $request->input('year_month');

        // Check if deposit exists
        $deposit = Deposit::find($depositId);
        if (!$deposit) {
            return Helper::errorResponse('Deposit not found', 404);
        }

        $result = $this->monthlyIncomeExpenseService->getDetailedMonthlyChanges($depositId, $yearMonth);
        $result['deposit'] = $deposit;

        return Helper::successResponse('Detailed monthly changes retrieved successfully', $result);
    }

    /**
     * Get available deposits for income/expense calculations
     *
     * @return JsonResponse
     */
    public function getAvailableDeposits(): JsonResponse
    {
        $deposits = Deposit::with(['organ', 'bank'])->get();

        return Helper::successResponse('Available deposits retrieved successfully', $deposits);
    }

    /**
     * Get available organs for income/expense calculations
     *
     * @return JsonResponse
     */
    public function getAvailableOrgans(): JsonResponse
    {
        $organs = Organ::with('deposits')->get();

        return Helper::successResponse('Available organs retrieved successfully', $organs);
    }

    /**
     * Get available months for a specific deposit
     *
     * @param int $depositId
     * @return JsonResponse
     */
    public function getAvailableMonths(int $depositId): JsonResponse
    {
        // Check if deposit exists
        $deposit = Deposit::find($depositId);
        if (!$deposit) {
            return Helper::errorResponse('Deposit not found', 404);
        }

        try {
            // Get all months that have balance data
            $balances = \DB::table('balances')
                ->where('deposit_id', (int)$depositId)
                ->where('rahkaran_status', 'success')
                ->where('rahkaran_balance', '!=', null)
                ->whereNotNull('rahkaran_fetched_at')
                ->select('rahkaran_fetched_at')
                ->get();

            $months = $balances->map(function ($balance) {
                return \Carbon\Carbon::parse($balance->rahkaran_fetched_at)->format('Y-m');
            })->unique()->sort()->reverse()->values();

            return Helper::successResponse('Available months retrieved successfully', [
                'deposit' => $deposit,
                'available_months' => $months,
            ]);
        } catch (\Exception $e) {
            return Helper::errorResponse('Error retrieving available months: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get available months for a specific organ
     *
     * @param int $organId
     * @return JsonResponse
     */
    public function getOrganAvailableMonths(int $organId): JsonResponse
    {
        // Check if organ exists
        $organ = Organ::with('deposits')->find($organId);
        if (!$organ) {
            return Helper::errorResponse('Organ not found', 404);
        }

        try {
            // Get all months that have balance data for any deposit of this organ
            $balances = \DB::table('balances')
                ->join('deposits', 'balances.deposit_id', '=', 'deposits.id')
                ->where('deposits.organ_id', (int)$organId)
                ->where('balances.rahkaran_status', 'success')
                ->where('balances.rahkaran_balance', '!=', null)
                ->whereNotNull('balances.rahkaran_fetched_at')
                ->select('balances.rahkaran_fetched_at')
                ->get();

            $months = $balances->map(function ($balance) {
                return \Carbon\Carbon::parse($balance->rahkaran_fetched_at)->format('Y-m');
            })->unique()->sort()->reverse()->values();

            return Helper::successResponse('Available months retrieved successfully', [
                'organ' => $organ,
                'available_months' => $months,
            ]);
        } catch (\Exception $e) {
            return Helper::errorResponse('Error retrieving available months: ' . $e->getMessage(), 500);
        }
    }
}
