<?php

use App\Constants\OrganPermissionKey;
use App\Http\Controllers\Api\V1\Organ\BankController;
use App\Http\Controllers\Api\V1\Organ\DepositController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'organ', 'middleware' => ['is-organ']], function () {
    Route::group(['prefix' => 'bank', 'middleware' => ['can:'.OrganPermissionKey::ORGAN]], function () {
        Route::get('/', [BankController::class, 'index'])->middleware('can:'.OrganPermissionKey::BANK_LIST);
        Route::get('/{bank}', [BankController::class, 'show'])->middleware('can:'.OrganPermissionKey::BANK_SHOW);
        Route::post('/', [BankController::class, 'store'])->middleware('can:'.OrganPermissionKey::BANK_CREATE);
        Route::put('/{bank}', [BankController::class, 'update'])->middleware('can:'.OrganPermissionKey::BANK_EDIT);
        Route::delete('/{bank}', [BankController::class, 'delete'])->middleware('can:'.OrganPermissionKey::BANK_DELETE);
    });

    Route::group(['prefix' => 'deposit', 'middleware' => ['can:'.OrganPermissionKey::DEPOSIT]], function () {
        Route::get('/', [DepositController::class, 'index'])->middleware('can:'.OrganPermissionKey::DEPOSIT_LIST);
        Route::get('/types', [DepositController::class, 'types'])->middleware('can:'.OrganPermissionKey::DEPOSIT_LIST);
        Route::get('/{deposit}', [DepositController::class, 'show'])->middleware('can:'.OrganPermissionKey::DEPOSIT_SHOW);
        Route::post('/', [DepositController::class, 'store'])->middleware('can:'.OrganPermissionKey::DEPOSIT_CREATE);
        Route::put('/{deposit}', [DepositController::class, 'update'])->middleware('can:'.OrganPermissionKey::DEPOSIT_EDIT);
        Route::delete('/{deposit}', [DepositController::class, 'delete'])->middleware('can:'.OrganPermissionKey::DEPOSIT_DELETE);
    });
});
