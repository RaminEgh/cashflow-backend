<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Deposit\StoreDepositRequest;
use App\Http\Requests\Admin\Deposit\UpdateDepositBankingApiAccessRequest;
use App\Http\Requests\Admin\Organ\UpdateOrganRequest;
use App\Http\Resources\V1\Admin\Deposit\DepositCollection;
use App\Http\Resources\V1\Admin\Deposit\DepositResource;
use App\Http\Resources\V1\Common\PaginationCollection;
use App\Jobs\FetchBankAccountBalance;
use App\Models\Deposit;
use App\Services\DepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function __construct(
        private readonly DepositService $depositService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'organ_id' => $request->organ_id,
            'bank_id' => $request->bank_id,
            'sort' => $request->sort,
            'sort_by' => $request->sort_by,
            'order' => $request->order,
            'sort_order' => $request->sort_order,
        ];

        $perPage = $request->perPage ?? $request->per_page ?? 10;
        $deposits = $this->depositService->getPaginated($filters, $perPage, $request->page ?? 1);

        return Helper::successResponse(null, [
            'list' => new DepositCollection($deposits),
            'pagination' => new PaginationCollection($deposits),
        ]);
    }

    public function store(StoreDepositRequest $request): JsonResponse
    {
        $deposit = $this->depositService->create($request->validated(), $request->user()->id);

        return Helper::successResponse(__('crud.d_created', ['source' => __('sources.organ'), 'name' => "حساب $deposit->number ساخته شد"]), new DepositResource($deposit));
    }

    public function show(Deposit $deposit): JsonResponse
    {
        $deposit = $this->depositService->getById($deposit->id) ?? $deposit->load(['organ', 'bank', 'balances']);

        return Helper::successResponse(null, new DepositResource($deposit));
    }

    public function updateBankingApiAccess(UpdateDepositBankingApiAccessRequest $request, Deposit $deposit): JsonResponse
    {
        $deposit = $this->depositService->updateBankingApiAccess(
            $deposit,
            $request->boolean('has_access_banking_api'),
            $request->user()->id
        );

        return Helper::successResponse('وضعیت دسترسی به API بانکی با موفقیت ویرایش شد.');
    }

    public function update(UpdateOrganRequest $request, Deposit $deposit): JsonResponse
    {
        $deposit = $this->depositService->update($deposit, $request->validated(), $request->user()->id);

        return Helper::successResponse('سازمان با موفقیت ویرایش شد.');
    }

    public function destroy(Deposit $deposit): JsonResponse
    {
        $this->depositService->delete($deposit);

        return Helper::successResponse('موفقیت آمیز');
    }

    public function updateBalance(Deposit $deposit): JsonResponse
    {
        FetchBankAccountBalance::dispatch($deposit)
            ->tags(['balance-update', 'api', 'admin', "deposit:{$deposit->number}"]);

        return Helper::successResponse(
            "درخواست به‌روزرسانی موجودی برای حساب {$deposit->number} با موفقیت ارسال شد.",
            [
                'deposit_id' => $deposit->id,
                'deposit_number' => $deposit->number,
            ]
        );
    }
}
