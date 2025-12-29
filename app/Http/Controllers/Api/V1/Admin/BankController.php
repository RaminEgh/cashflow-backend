<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBankRequest;
use App\Http\Requests\UpdateBankRequest;
use App\Http\Resources\V1\Common\BankResource;
use App\Http\Resources\V1\Common\PaginationCollection;
use App\Jobs\FetchBankAccountBalance;
use App\Models\Bank;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class BankController extends Controller
{
    public function index(): JsonResponse
    {
        $banks = Bank::paginate($request->per_page ?? 10);

        return Helper::successResponse(null, [
            'list' => BankResource::collection($banks),
            'pagination' => new PaginationCollection($banks),
        ]);
    }

    public function store(StoreBankRequest $request): JsonResponse
    {
        $bank = Bank::create([
            'name' => $request->name,
            'en_name' => $request->en_name,
            'slug' => Str::slug($request->en_name),
            'logo' => $request->logo ?? '',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return Helper::successResponse(__('crud.d_created', ['source' => __('sources.bank'), 'name' => $bank->name]), new BankResource($bank));
    }

    public function show(Bank $bank): JsonResponse
    {
        return Helper::successResponse('', new BankResource($bank));
    }

    public function update(UpdateBankRequest $request, Bank $bank)
    {
        $bank->update($request->validated());

        return Helper::successResponse(__('crud.d_edited', ['source' => __('sources.bank'), 'name' => $bank->name]), new BankResource($bank));
    }

    public function destroy(Bank $bank)
    {
        //
    }

    public function updateBalances(Bank $bank): JsonResponse
    {
        $deposits = $bank->deposits;

        if ($deposits->isEmpty()) {
            return Helper::errorResponse(__('No deposits found for this bank'), [], 404);
        }

        foreach ($deposits as $deposit) {
            FetchBankAccountBalance::dispatch($deposit)
                ->tags(['balance-update', 'api', 'admin', "bank:{$bank->slug}"]);
        }

        return Helper::successResponse(
            __('Balance update jobs dispatched successfully for bank: :name', ['name' => $bank->name]),
            [
                'bank_id' => $bank->id,
                'bank_name' => $bank->name,
                'deposits_count' => $deposits->count(),
            ]
        );
    }
}
