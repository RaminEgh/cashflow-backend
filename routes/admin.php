<?php

use App\Constants\AdminPermissionKey;
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\AllocationController;
use App\Http\Controllers\Api\V1\Admin\BankController;
use App\Http\Controllers\Api\V1\Admin\DepositController;
use App\Http\Controllers\Api\V1\Admin\MonthlyIncomeExpenseController;
use App\Http\Controllers\Api\V1\Admin\OrganController;
use App\Http\Controllers\Api\V1\Admin\PermissionController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\SettingController;
use App\Http\Controllers\Api\V1\Admin\TimelineController;
use App\Http\Controllers\Api\V1\Admin\UploadController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Auth\NewPasswordController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin', 'middleware' => ['is-admin']], function () {

    Route::post('/reset-password', [NewPasswordController::class, 'store']);

    Route::group(['prefix' => 'monthly-income-expense', 'middleware' => ['can:' . AdminPermissionKey::MONTHLY_INCOME_EXPENSE]], function () {
        // Deposit-level endpoints
        Route::get('/deposits', [MonthlyIncomeExpenseController::class, 'getAvailableDeposits'])->middleware('can:' . AdminPermissionKey::MONTHLY_INCOME_EXPENSE_LIST);
        Route::get('/deposits/{depositId}/months', [MonthlyIncomeExpenseController::class, 'getAvailableMonths'])->middleware('can:' . AdminPermissionKey::MONTHLY_INCOME_EXPENSE_LIST);
        Route::get('/deposits/{depositId}', [MonthlyIncomeExpenseController::class, 'getMonthlyIncomeExpense'])->middleware('can:' . AdminPermissionKey::MONTHLY_INCOME_EXPENSE_LIST);
        Route::get('/deposits/{depositId}/yearly-summary', [MonthlyIncomeExpenseController::class, 'getYearlySummary'])->middleware('can:' . AdminPermissionKey::MONTHLY_INCOME_EXPENSE_LIST);
        Route::get('/deposits/{depositId}/detailed-changes', [MonthlyIncomeExpenseController::class, 'getDetailedMonthlyChanges'])->middleware('can:' . AdminPermissionKey::MONTHLY_INCOME_EXPENSE_LIST);
        Route::get('/all-deposits', [MonthlyIncomeExpenseController::class, 'getAllDepositsMonthlyIncomeExpense'])->middleware('can:' . AdminPermissionKey::MONTHLY_INCOME_EXPENSE_LIST);

        // Organ-level endpoints
        Route::get('/organs', [MonthlyIncomeExpenseController::class, 'getAvailableOrgans'])->middleware('can:' . AdminPermissionKey::MONTHLY_INCOME_EXPENSE_LIST);
        Route::get('/organs/{organId}/months', [MonthlyIncomeExpenseController::class, 'getOrganAvailableMonths'])->middleware('can:' . AdminPermissionKey::MONTHLY_INCOME_EXPENSE_LIST);
        Route::get('/organs/{organId}', [MonthlyIncomeExpenseController::class, 'getOrganMonthlyIncomeExpense'])->middleware('can:' . AdminPermissionKey::MONTHLY_INCOME_EXPENSE_LIST);
        Route::get('/organs/{organId}/yearly-summary', [MonthlyIncomeExpenseController::class, 'getOrganYearlySummary'])->middleware('can:' . AdminPermissionKey::MONTHLY_INCOME_EXPENSE_LIST);
        Route::get('/all-organs', [MonthlyIncomeExpenseController::class, 'getAllOrgansMonthlyIncomeExpense'])->middleware('can:' . AdminPermissionKey::MONTHLY_INCOME_EXPENSE_LIST);
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
        Route::post('/', [OrganController::class, 'store'])->middleware('can:' . AdminPermissionKey::ORGAN_CREATE);
        Route::get('/{organ}', [OrganController::class, 'show'])->middleware('can:' . AdminPermissionKey::ORGAN_SHOW);
        Route::put('/{organ}', [OrganController::class, 'update'])->middleware('can:' . AdminPermissionKey::ORGAN_EDIT);
        Route::delete('/{organ}', [OrganController::class, 'delete'])->middleware('can:' . AdminPermissionKey::ORGAN_DELETE);
        Route::patch('/{organ}/assign', [OrganController::class, 'assign'])->middleware('can:' . AdminPermissionKey::ORGAN_ASSIGN_ADMIN);
        Route::get('/{organ}/allocation', [OrganController::class, 'allocation'])->middleware('can:' . AdminPermissionKey::ORGAN_SHOW);
        Route::post('/{organ}/update-balances', [OrganController::class, 'updateBalances'])->middleware('can:' . AdminPermissionKey::ORGAN_EDIT);
    });

    Route::group(['prefix' => 'allocation', 'middleware' => ['can:' . AdminPermissionKey::ALLOCATION]], function () {
        Route::get('/', [AllocationController::class, 'index'])->middleware('can:' . AdminPermissionKey::ALLOCATION_LIST);
        Route::get('/organ/{organ}', [AllocationController::class, 'index'])->middleware('can:' . AdminPermissionKey::ALLOCATION_LIST);
        Route::post('/', [AllocationController::class, 'store'])->middleware('can:' . AdminPermissionKey::ALLOCATION_CREATE);
        Route::get('/{allocation}', [AllocationController::class, 'show'])->middleware('can:' . AdminPermissionKey::ALLOCATION_SHOW);
        Route::put('/{allocation}', [AllocationController::class, 'update'])->middleware('can:' . AdminPermissionKey::ALLOCATION_EDIT);
    });

    Route::group(['prefix' => 'deposit', 'middleware' => ['can:' . AdminPermissionKey::DEPOSIT]], function () {
        Route::get('/', [DepositController::class, 'index'])->middleware('can:' . AdminPermissionKey::DEPOSIT_LIST);
        Route::get('/{deposit}', [DepositController::class, 'show'])->middleware('can:' . AdminPermissionKey::DEPOSIT_SHOW);
        Route::post('/', [DepositController::class, 'store'])->middleware('can:' . AdminPermissionKey::DEPOSIT_CREATE);
        Route::put('/{deposit}', [DepositController::class, 'update'])->middleware('can:' . AdminPermissionKey::DEPOSIT_EDIT);
        Route::patch('/{deposit}/banking-api-access', [DepositController::class, 'updateBankingApiAccess'])->middleware('can:' . AdminPermissionKey::DEPOSIT_EDIT);
        Route::delete('/{deposit}', [DepositController::class, 'delete'])->middleware('can:' . AdminPermissionKey::DEPOSIT_DELETE);
        Route::post('/{deposit}/update-balance', [DepositController::class, 'updateBalance'])->middleware('can:' . AdminPermissionKey::DEPOSIT_EDIT);
    });

    Route::group(['prefix' => 'bank', 'middleware' => ['can:' . AdminPermissionKey::BANK]], function () {
        Route::get('/', [BankController::class, 'index'])->middleware('can:' . AdminPermissionKey::BANK_LIST);
        Route::get('/{bank}', [BankController::class, 'show'])->middleware('can:' . AdminPermissionKey::BANK_SHOW);
        Route::post('/', [BankController::class, 'store'])->middleware('can:' . AdminPermissionKey::BANK_CREATE);
        Route::put('/{bank}', [BankController::class, 'update'])->middleware('can:' . AdminPermissionKey::BANK_EDIT);
        Route::delete('/{bank}', [BankController::class, 'delete'])->middleware('can:' . AdminPermissionKey::BANK_DELETE);
        Route::post('/{bank}/update-balances', [BankController::class, 'updateBalances'])->middleware('can:' . AdminPermissionKey::BANK_EDIT);
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

    Route::group(['prefix' => 'uploads', 'middleware' => ['can:' . AdminPermissionKey::UPLOAD]], function () {
        Route::get('/', [UploadController::class, 'index'])->middleware('can:' . AdminPermissionKey::UPLOAD_LIST);
        Route::get('/statistics', [UploadController::class, 'statistics'])->middleware('can:' . AdminPermissionKey::UPLOAD_STATISTICS);
        Route::get('/user-statistics', [UploadController::class, 'userStatistics'])->middleware('can:' . AdminPermissionKey::UPLOAD_STATISTICS);
        Route::post('/bulk-delete', [UploadController::class, 'bulkDelete'])->middleware('can:' . AdminPermissionKey::UPLOAD_BULK_DELETE);
        Route::get('/{upload}', [UploadController::class, 'show'])->middleware('can:' . AdminPermissionKey::UPLOAD_SHOW);
        Route::delete('/{upload}', [UploadController::class, 'destroy'])->middleware('can:' . AdminPermissionKey::UPLOAD_DELETE);
    });

    Route::group(['prefix' => 'settings', 'middleware' => ['can:' . AdminPermissionKey::SETTING]], function () {
        Route::get('/', [SettingController::class, 'all'])->middleware('can:' . AdminPermissionKey::SETTING_LIST);
        Route::get('/get', [SettingController::class, 'get'])->middleware('can:' . AdminPermissionKey::SETTING_GET);
        Route::post('/set', [SettingController::class, 'set'])->middleware('can:' . AdminPermissionKey::SETTING_SET);
        Route::post('/get-multiple', [SettingController::class, 'getMultiple'])->middleware('can:' . AdminPermissionKey::SETTING_GET_MULTIPLE);
        Route::post('/set-multiple', [SettingController::class, 'setMultiple'])->middleware('can:' . AdminPermissionKey::SETTING_SET_MULTIPLE);
        Route::get('/has', [SettingController::class, 'has'])->middleware('can:' . AdminPermissionKey::SETTING_HAS);
        Route::delete('/delete', [SettingController::class, 'delete'])->middleware('can:' . AdminPermissionKey::SETTING_DELETE);
        Route::get('/by-prefix', [SettingController::class, 'getByPrefix'])->middleware('can:' . AdminPermissionKey::SETTING_BY_PREFIX);
        Route::delete('/by-prefix', [SettingController::class, 'deleteByPrefix'])->middleware('can:' . AdminPermissionKey::SETTING_DELETE_BY_PREFIX);
        Route::post('/clear-cache', [SettingController::class, 'clearCache'])->middleware('can:' . AdminPermissionKey::SETTING_CLEAR_CACHE);
    });

    Route::group(['prefix' => 'timeline', 'middleware' => ['can:' . AdminPermissionKey::TIMELINE]], function () {
        Route::get('/grouped/{organ}', [TimelineController::class, 'grouped'])->middleware('can:' . AdminPermissionKey::TIMELINE_GROUPED);
        Route::get('/{organ}', [TimelineController::class, 'show'])->middleware('can:' . AdminPermissionKey::TIMELINE_SHOW);
        Route::get('/{organ}/summary', [TimelineController::class, 'summary'])->middleware('can:' . AdminPermissionKey::TIMELINE_SUMMARY);
        Route::post('/{organ}/refresh', [TimelineController::class, 'refresh'])->middleware('can:' . AdminPermissionKey::TIMELINE_REFRESH);
    });
});
