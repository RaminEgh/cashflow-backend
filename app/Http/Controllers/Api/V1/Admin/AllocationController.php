<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Allocation\StoreAllocationRequest;
use App\Http\Requests\Admin\Allocation\UpdateAllocationRequest;
use App\Http\Resources\V1\Admin\Allocation\AllocationListCollection;
use App\Http\Resources\V1\Admin\Allocation\AllocationResource;
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

    public function index(Request $request): JsonResponse
    {
        $allocation = Allocation::all();

        return Helper::successResponse(null, new AllocationListCollection($allocation));
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
