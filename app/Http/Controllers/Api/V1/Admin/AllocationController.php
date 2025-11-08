<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Allocation\StoreAllocationRequest;
use App\Http\Requests\Admin\Allocation\UpdateAllocationRequest;
use App\Http\Resources\V1\Admin\Organ\AllocationResource;
use App\Models\Allocation;
use App\Models\Organ;
use App\Services\Banking\IncomeOutgoingService as BankingIncomeOutgoingService;
use App\Services\Rahkaran\IncomeOutgoingService as RahkaranIncomeOutgoingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AllocationController extends Controller
{
    public function __construct(
        private readonly RahkaranIncomeOutgoingService $rahkaranIncomeOutgoingService,
        private readonly BankingIncomeOutgoingService $bankingIncomeOutgoingService,
    ) {}

    public function index(Request $request, Organ $organ): JsonResponse
    {
        $year = $request->year;

        if (! $year) {
            $latestAllocation = $organ->allocations()
                ->orderBy('year', 'desc')
                ->first();

            if (! $latestAllocation) {
                return Helper::successResponse('No data found', [
                    'list' => [],
                ]);
            }

            $year = $latestAllocation->year;
        }

        $allocation = $organ->allocations()
            ->where('year', $year)
            ->first();

        if (! $allocation) {
            return Helper::successResponse('No data found', [
                'list' => [],
            ]);
        }

        $months = [
            1 => 'فروردین',
            2 => 'اردیبهشت',
            3 => 'خرداد',
            4 => 'تیر',
            5 => 'مرداد',
            6 => 'شهریور',
            7 => 'مهر',
            8 => 'آبان',
            9 => 'آذر',
            10 => 'دی',
            11 => 'بهمن',
            12 => 'اسفند',
        ];

        $result = [];
        foreach ($months as $num => $name) {
            $rahkaranIncomeOutgoing = $this->rahkaranIncomeOutgoingService->calculateOrganMonthlyIncomeOutgoing($organ, "{$year}/{$num}");
            $bankIncomeOutgoing = $this->bankingIncomeOutgoingService->calculateOrganMonthlyIncomeOutgoing($organ, "{$year}/{$num}");
            $result[] = [
                'id' => $num + 1,
                'month' => $name,
                'budget' => $allocation["month_{$num}_budget"],
                'expense' => $allocation["month_{$num}_expense"],
                'bank_income' => $bankIncomeOutgoing['total_income'] ?? 0,
                'bank_outgoing' => $bankIncomeOutgoing['total_outgoing'] ?? 0,
                'rahkaran_income' => $rahkaranIncomeOutgoing['total_income'] ?? 0,
                'rahkaran_outgoing' => $rahkaranIncomeOutgoing['total_outgoing'] ?? 0,
            ];
        }

        return Helper::successResponse(null, $result);
    }

    public function store(StoreAllocationRequest $request): JsonResponse
    {
        $organ = Organ::findOrFail($request->organ_id);

        $existingAllocation = Allocation::where('organ_id', $organ->id)
            ->where('year', $request->year)
            ->first();

        if ($existingAllocation) {
            return Helper::errorResponse("بودجه برای سال {$request->year} قبلاً برای این سازمان ثبت شده است.", [], 422);
        }

        $allocation = Allocation::create([
            'organ_id' => $organ->id,
            'year' => $request->year,
            'description' => $request->description,
            'month_1_budget' => $request->month_1_budget ?? 0,
            'month_2_budget' => $request->month_2_budget ?? 0,
            'month_3_budget' => $request->month_3_budget ?? 0,
            'month_4_budget' => $request->month_4_budget ?? 0,
            'month_5_budget' => $request->month_5_budget ?? 0,
            'month_6_budget' => $request->month_6_budget ?? 0,
            'month_7_budget' => $request->month_7_budget ?? 0,
            'month_8_budget' => $request->month_8_budget ?? 0,
            'month_9_budget' => $request->month_9_budget ?? 0,
            'month_10_budget' => $request->month_10_budget ?? 0,
            'month_11_budget' => $request->month_11_budget ?? 0,
            'month_12_budget' => $request->month_12_budget ?? 0,
            'month_1_expense' => $request->month_1_expense ?? 0,
            'month_2_expense' => $request->month_2_expense ?? 0,
            'month_3_expense' => $request->month_3_expense ?? 0,
            'month_4_expense' => $request->month_4_expense ?? 0,
            'month_5_expense' => $request->month_5_expense ?? 0,
            'month_6_expense' => $request->month_6_expense ?? 0,
            'month_7_expense' => $request->month_7_expense ?? 0,
            'month_8_expense' => $request->month_8_expense ?? 0,
            'month_9_expense' => $request->month_9_expense ?? 0,
            'month_10_expense' => $request->month_10_expense ?? 0,
            'month_11_expense' => $request->month_11_expense ?? 0,
            'month_12_expense' => $request->month_12_expense ?? 0,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return Helper::successResponse(__('crud.d_created', ['source' => __('sources.allocation'), 'name' => "بودجه سال {$allocation->year}"]), new AllocationResource($allocation));
    }

    public function show(Allocation $allocation): JsonResponse
    {
        return Helper::successResponse(null, new AllocationResource($allocation));
    }

    public function update(UpdateAllocationRequest $request, Allocation $allocation): JsonResponse
    {
        $validated = array_filter($request->validated(), fn ($value) => $value !== null);
        $validated['updated_by'] = auth()->id();

        $allocation->update($validated);

        return Helper::successResponse('بودجه با موفقیت ویرایش شد.', new AllocationResource($allocation));
    }
}
