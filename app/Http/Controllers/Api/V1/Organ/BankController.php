<?php

namespace App\Http\Controllers\Api\V1\Organ;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBankRequest;
use App\Http\Requests\UpdateBankRequest;
use App\Http\Resources\V1\Admin\Organ\OrganResource;
use App\Http\Resources\V1\Common\BankResource;
use App\Http\Resources\V1\Common\PaginationCollection;
use App\Models\Bank;
use App\Models\Organ;
use Illuminate\Support\Str;

class BankController extends Controller
{
    public function index()
    {
        $banks = Bank::paginate($request->per_page ?? 10);
        return Helper::successResponse(null, [
            'list' =>  BankResource::collection($banks),
            'pagination' => new PaginationCollection($banks)
        ]);
    }


    public function store(StoreBankRequest $request)
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


    public function show(Bank $bank)
    {
        return Helper::successResponse('', new BankResource($bank));
    }


    public function update(UpdateBankRequest $request, Bank $bank)
    {
        //
    }

    public function destroy(Bank $bank)
    {
        //
    }
}
