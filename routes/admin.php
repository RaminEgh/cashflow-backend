<?php

use App\Constants\AdminPermissionKey;
use App\Http\Controllers\Api\V1\Admin\AccessController;
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\BankController;
use App\Http\Controllers\Api\V1\Admin\DepositController;
use App\Http\Controllers\Api\V1\Admin\OrganController;
use App\Http\Controllers\Api\V1\Admin\PermissionController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Auth\NewPasswordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\MonthlyIncomeExpenseController;

Route::group(['prefix' => 'admin', 'middleware' => ['is-admin']], function () {

    Route::post('/reset-password', [NewPasswordController::class, 'store']);

    // Monthly Income/Expense Routes (public for now, can be moved to auth group if needed)
    Route::group(['prefix' => 'monthly-income-expense'], function () {
        // Deposit-level endpoints
        Route::get('/deposits', [MonthlyIncomeExpenseController::class, 'getAvailableDeposits']);
        Route::get('/deposits/{depositId}/months', [MonthlyIncomeExpenseController::class, 'getAvailableMonths']);
        Route::get('/deposits/{depositId}', [MonthlyIncomeExpenseController::class, 'getMonthlyIncomeExpense']);
        Route::get('/deposits/{depositId}/yearly-summary', [MonthlyIncomeExpenseController::class, 'getYearlySummary']);
        Route::get('/deposits/{depositId}/detailed-changes', [MonthlyIncomeExpenseController::class, 'getDetailedMonthlyChanges']);
        Route::get('/all-deposits', [MonthlyIncomeExpenseController::class, 'getAllDepositsMonthlyIncomeExpense']);

        // Organ-level endpoints
        Route::get('/organs', [MonthlyIncomeExpenseController::class, 'getAvailableOrgans']);
        Route::get('/organs/{organId}/months', [MonthlyIncomeExpenseController::class, 'getOrganAvailableMonths']);
        Route::get('/organs/{organId}', [MonthlyIncomeExpenseController::class, 'getOrganMonthlyIncomeExpense']);
        Route::get('/organs/{organId}/yearly-summary', [MonthlyIncomeExpenseController::class, 'getOrganYearlySummary']);
        Route::get('/all-organs', [MonthlyIncomeExpenseController::class, 'getAllOrgansMonthlyIncomeExpense']);
    });

    Route::group(['prefix' => 'user', 'middleware' => ['can:' . AdminPermissionKey::USER]], function () {
        Route::get('/', [UserController::class, 'index'])->middleware('can:' . AdminPermissionKey::USER_LIST);
        Route::get('/status', [UserController::class, 'statuses'])->middleware('can:' . AdminPermissionKey::USER_SHOW);
        Route::get('/{user}', [UserController::class, 'show'])->middleware('can:' . AdminPermissionKey::USER_SHOW);
        Route::post('/', [UserController::class, 'store'])->middleware('can:' . AdminPermissionKey::USER_CREATE);
        Route::put('/{user}', [UserController::class, 'update'])->middleware('can:' . AdminPermissionKey::USER_EDIT);
        Route::delete('/{user}', [UserController::class, 'delete'])->middleware('can:' . AdminPermissionKey::USER_DELETE);
        Route::patch('/{user}/block', [UserController::class, 'block'])->middleware('can:' . AdminPermissionKey::USER_BLOCK);
        Route::patch('/{user}/unblock', [UserController::class, 'unblock'])->middleware('can:' . AdminPermissionKey::USER_UNBLOCK);
    });

    Route::group(['prefix' => 'organ', 'middleware' => ['can:' . AdminPermissionKey::ORGAN]], function () {
        Route::get('/', [OrganController::class, 'index'])->middleware('can:' . AdminPermissionKey::ORGAN_LIST);
        Route::get('/{organ}', [OrganController::class, 'show'])->middleware('can:' . AdminPermissionKey::ORGAN_SHOW);
        Route::post('/', [OrganController::class, 'store'])->middleware('can:' . AdminPermissionKey::ORGAN_CREATE);
        Route::put('/{organ}', [OrganController::class, 'update'])->middleware('can:' . AdminPermissionKey::ORGAN_EDIT);
        Route::delete('/{organ}', [OrganController::class, 'delete'])->middleware('can:' . AdminPermissionKey::ORGAN_DELETE);
        Route::patch('/{organ}/assign', [OrganController::class, 'assign'])->middleware('can:' . AdminPermissionKey::ORGAN_ASSIGN_ADMIN);
        Route::get('/{organ}/allocation', [OrganController::class, 'allocation'])->middleware('can:' . AdminPermissionKey::ORGAN_SHOW);
    });

    Route::group(['prefix' => 'deposit', 'middleware' => ['can:' . AdminPermissionKey::DEPOSIT]], function () {
        Route::get('/', [DepositController::class, 'index'])->middleware('can:' . AdminPermissionKey::ORGAN_LIST);
        Route::get('/{deposit}', [DepositController::class, 'show'])->middleware('can:' . AdminPermissionKey::ORGAN_SHOW);
        Route::post('/', [DepositController::class, 'store'])->middleware('can:' . AdminPermissionKey::ORGAN_CREATE);
        Route::put('/{deposit}', [DepositController::class, 'update'])->middleware('can:' . AdminPermissionKey::ORGAN_EDIT);
        Route::delete('/{deposit}', [DepositController::class, 'delete'])->middleware('can:' . AdminPermissionKey::ORGAN_DELETE);
    });

    Route::group(['prefix' => 'bank', 'middleware' => ['can:' . AdminPermissionKey::BANK]], function () {
        Route::get('/', [BankController::class, 'index'])->middleware('can:' . AdminPermissionKey::BANK_LIST);
        Route::get('/{bank}', [BankController::class, 'show'])->middleware('can:' . AdminPermissionKey::BANK_SHOW);
        Route::post('/', [BankController::class, 'store'])->middleware('can:' . AdminPermissionKey::BANK_CREATE);
        Route::put('/{bank}', [BankController::class, 'update'])->middleware('can:' . AdminPermissionKey::BANK_EDIT);
        Route::delete('/{bank}', [BankController::class, 'delete'])->middleware('can:' . AdminPermissionKey::BANK_DELETE);
    });

    Route::group(['prefix' => 'admin', 'middleware' => ['can:' . AdminPermissionKey::ADMIN_ADMIN]], function () {
        Route::get('/', [AdminController::class, 'index'])->middleware('can:' . AdminPermissionKey::ADMIN_ADMIN_LIST);
        Route::post('/', [AdminController::class, 'store'])->middleware('can:' . AdminPermissionKey::ADMIN_ADMIN_CREATE);
        Route::get('/{user}', [AdminController::class, 'show'])->middleware('can:' . AdminPermissionKey::ADMIN_ADMIN_SHOW);
        Route::put('/{user}', [AdminController::class, 'update'])->middleware('can:' . AdminPermissionKey::ADMIN_ADMIN_EDIT);
        Route::delete('/{user}', [AdminController::class, 'delete'])->middleware('can:' . AdminPermissionKey::ADMIN_ADMIN_DELETE);
    });

    Route::group(['prefix' => 'permission', 'middleware' => ['can:' . AdminPermissionKey::PERMISSION]], function () {
        Route::get('/', [PermissionController::class, 'index'])->middleware('can:' . AdminPermissionKey::PERMISSION_LIST);
        Route::get('/{permission}', [PermissionController::class, 'show'])->middleware('can:' . AdminPermissionKey::PERMISSION_SHOW);
        Route::put('/{permission}', [PermissionController::class, 'update'])->middleware('can:' . AdminPermissionKey::PERMISSION_EDIT);
    });

    Route::group(['prefix' => 'role', 'middleware' => ['can:' . AdminPermissionKey::PERMISSION]], function () {
        Route::get('/', [RoleController::class, 'index'])->middleware('can:' . AdminPermissionKey::ROLE_LIST);
        Route::post('/', [RoleController::class, 'store'])->middleware('can:' . AdminPermissionKey::ROLE_CREATE);
        Route::get('/{role}', [RoleController::class, 'show'])->middleware('can:' . AdminPermissionKey::ROLE_SHOW);
        Route::put('/{role}', [RoleController::class, 'update'])->middleware('can:' . AdminPermissionKey::ROLE_EDIT);
    });

    Route::group(['prefix' => 'access', 'middleware' => ['can:' . AdminPermissionKey::ACCESS]], function () {
        Route::get('/', [AccessController::class, 'index'])->middleware('can:' . AdminPermissionKey::ACCESS_LIST);
        Route::get('/{user}', [AccessController::class, 'show'])->middleware('can:' . AdminPermissionKey::ACCESS_LIST);
        Route::put('/{user}', [AccessController::class, 'update'])->middleware('can:' . AdminPermissionKey::ACCESS_ASSIGN);
    });

});

