<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Deposit\StoreDepositRequest;
use App\Http\Requests\Admin\Organ\UpdateOrganRequest;
use App\Http\Resources\V1\Admin\Deposit\DepositCollection;
use App\Http\Resources\V1\Admin\Deposit\DepositResource;
use App\Http\Resources\V1\Admin\Organ\OrganResource;
use App\Http\Resources\V1\Common\PaginationCollection;
use App\Models\Deposit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $deposits = Deposit::paginate($request->per_page ?? 10);
        return Helper::successResponse(null, [
            'list' =>  new DepositCollection($deposits),
            'pagination' => new PaginationCollection($deposits)
        ]);
    }

    public function store(StoreDepositRequest $request): JsonResponse
    {
        $deposit = Deposit::create([...$request->validated(), 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        return Helper::successResponse(__('crud.d_created', ['source' => __('sources.organ'), 'name' => "حساب $deposit->number ساخته شد"]), new DepositResource($deposit));
    }

    public function show(Deposit $deposit): JsonResponse
    {
        return Helper::successResponse(null, new OrganResource($deposit));
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
