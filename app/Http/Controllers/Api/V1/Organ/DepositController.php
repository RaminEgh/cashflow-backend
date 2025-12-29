<?php

namespace App\Http\Controllers\Api\V1\Organ;

use App\Enums\DepositType;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Deposit\UpdateDepositRequest;
use App\Http\Requests\Admin\Deposit\StoreDepositRequest;
use App\Http\Resources\V1\Common\DepositResource;
use App\Http\Resources\V1\Common\PaginationCollection;
use App\Jobs\FetchBankAccountBalance;
use App\Models\Deposit;
use Illuminate\Http\JsonResponse;

class DepositController extends Controller
{
    public function index(): JsonResponse
    {
        $deposits = Deposit::paginate($request->per_page ?? 10);

        return Helper::successResponse(null, [
            'list' => DepositResource::collection($deposits),
            'pagination' => new PaginationCollection($deposits),
        ]);
    }

    public function store(StoreDepositRequest $request)
    {
        $user = auth()->user();
        $deposit = Deposit::create([...$request->validated(), 'organ_id' => $user->organs()->first()->id, 'currency' => 'IR-Rial', 'created_by' => auth()->user()->id, 'updated_by' => auth()->user()->id]);

        return Helper::successResponse(__('crud.d_created', ['source' => __('sources.deposit'), 'name' => $deposit->name]), new DepositResource($deposit));
    }

    public function show(Deposit $deposit)
    {
        return Helper::successResponse('', new DepositResource($deposit));
    }

    public function update(UpdateDepositRequest $request, Deposit $deposit)
    {
        //
    }

    public function destroy(Deposit $deposit)
    {
        //
    }

    public function types(): JsonResponse
    {
        return Helper::successResponse(null, DepositType::keyValue());
    }

    public function updateBalance(Deposit $deposit): JsonResponse
    {
        FetchBankAccountBalance::dispatch($deposit)
            ->tags(['balance-update', 'api', "deposit:{$deposit->number}"]);

        return Helper::successResponse(
            __('Balance update job dispatched successfully for deposit: :number', ['number' => $deposit->number]),
            [
                'deposit_id' => $deposit->id,
                'deposit_number' => $deposit->number,
            ]
        );
    }

    public function updateBalancesForOrgan(): JsonResponse
    {
        $user = auth()->user();
        $organ = $user->organs()->first();

        if (! $organ) {
            return Helper::errorResponse(__('No organ found for the authenticated user'), [], 404);
        }

        $deposits = $organ->deposits;

        if ($deposits->isEmpty()) {
            return Helper::errorResponse(__('No deposits found for this organ'), [], 404);
        }

        foreach ($deposits as $deposit) {
            FetchBankAccountBalance::dispatch($deposit)
                ->tags(['balance-update', 'api', "organ:{$organ->slug}"]);
        }

        return Helper::successResponse(
            __('Balance update jobs dispatched successfully for organ: :name', ['name' => $organ->name]),
            [
                'organ_id' => $organ->id,
                'organ_name' => $organ->name,
                'deposits_count' => $deposits->count(),
            ]
        );
    }
}
