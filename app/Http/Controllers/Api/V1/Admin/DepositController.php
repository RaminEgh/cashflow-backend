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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepositController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Deposit::query();

        // Support direct query parameters for filtering
        if ($request->has('organ_id')) {
            $query->where('organ_id', $request->organ_id);
        }

        if ($request->has('bank_id')) {
            $query->where('bank_id', $request->bank_id);
        }

        // Handle sorting - support both 'sort' and 'sort_by'
        $sortField = $request->sort ?? $request->sort_by;
        $sortOrder = $request->order ?? $request->sort_order ?? 'ASC';

        if ($sortField) {
            // Handle relationship-based sorting (e.g., organ.name)
            if (str_contains($sortField, '.')) {
                [$relation, $field] = explode('.', $sortField, 2);

                if ($relation === 'organ') {
                    $query->join('organs', 'deposits.organ_id', '=', 'organs.id')
                        ->select('deposits.*')
                        ->orderBy("organs.{$field}", $sortOrder);
                } elseif ($relation === 'bank') {
                    $query->join('banks', 'deposits.bank_id', '=', 'banks.id')
                        ->select('deposits.*')
                        ->orderBy("banks.{$field}", $sortOrder);
                }
            } else {
                $query->orderBy($sortField, $sortOrder);
            }
        } else {
            $query->latest();
        }

        $perPage = $request->perPage ?? $request->per_page ?? 500;
        $deposits = $query->paginate($perPage);

        return Helper::successResponse(null, [
            'list' => new DepositCollection($deposits),
            'pagination' => new PaginationCollection($deposits),
        ]);
    }

    public function store(StoreDepositRequest $request): JsonResponse
    {
        $deposit = Deposit::create([...$request->validated(), 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);

        return Helper::successResponse(__('crud.d_created', ['source' => __('sources.organ'), 'name' => "حساب $deposit->number ساخته شد"]), new DepositResource($deposit));
    }

    public function show(Deposit $deposit): JsonResponse
    {
        return Helper::successResponse(null, new DepositResource($deposit));
    }

    public function updateBankingApiAccess(UpdateDepositBankingApiAccessRequest $request, Deposit $deposit): JsonResponse
    {
        DB::transaction(function () use ($request, $deposit) {
            $deposit->has_access_banking_api = $request->boolean('has_access_banking_api');
            $deposit->updated_by = $request->user()->id;
            $deposit->save();

            if ($deposit->has_access_banking_api) {
                FetchBankAccountBalance::dispatch($deposit);
            }
        });

        return Helper::successResponse('وضعیت دسترسی به API بانکی با موفقیت ویرایش شد.');
    }

    public function update(UpdateOrganRequest $request, Deposit $deposit): JsonResponse
    {
        $deposit->update($request->validated());

        return Helper::successResponse('سازمان با موفقیت ویرایش شد.');
    }

    public function destroy(Deposit $deposit): JsonResponse
    {
        $deposit->delete();

        return Helper::successResponse('موفقیت آمیز');
    }
}
