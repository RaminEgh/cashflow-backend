<?php

use App\Helpers\Helper;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\Api\V1\Admin\SettingController;
use App\Http\Controllers\Api\V1\Admin\TimelineController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';

Route::get('/test', function () {
    return Helper::successResponse('', ['this is ' => 'test']);
});

Route::post('/debug', function () {
    return response()->json(['ok' => true]);
});

Route::group(['prefix' => 'mock'], function () {
    Route::post('/saman', function () {
        return Helper::successResponse('', ['balance' => 1_000_000_000]);
    });

    Route::post('/mellat', function () {
        return Helper::successResponse('', ['balance' => 800_000_000]);
    });

    Route::post('/parsian', function () {
        return Helper::successResponse('', ['balance' => 550_000_000]);
    });
});



Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('/user', function () {
        return Helper::successResponse('', auth()->user());
    });

    require __DIR__ . '/admin.php';
    require __DIR__ . '/organ.php';


    Route::group(['prefix' => 'upload'], function () {
        Route::get('/', [UploadController::class, 'index']);
        Route::get('/{upload}', [UploadController::class, 'show']);
        Route::post('/', [UploadController::class, 'store']);
        Route::get('/{upload}/download', [UploadController::class, 'download']);
        Route::delete('/{upload}', [UploadController::class, 'destroy']);
    });

    // Settings routes
    Route::group(['prefix' => 'settings'], function () {
        Route::get('/', [SettingController::class, 'all']);
        Route::get('/get', [SettingController::class, 'get']);
        Route::post('/set', [SettingController::class, 'set']);
        Route::post('/get-multiple', [SettingController::class, 'getMultiple']);
        Route::post('/set-multiple', [SettingController::class, 'setMultiple']);
        Route::get('/has', [SettingController::class, 'has']);
        Route::delete('/delete', [SettingController::class, 'delete']);
        Route::get('/by-prefix', [SettingController::class, 'getByPrefix']);
        Route::delete('/by-prefix', [SettingController::class, 'deleteByPrefix']);
        Route::post('/clear-cache', [SettingController::class, 'clearCache']);
    });

    // Timeline routes
    Route::group(['prefix' => 'timeline'], function () {
        Route::get('/grouped/{organ}', [TimelineController::class, 'grouped']);
        Route::get('/{organ}', [TimelineController::class, 'show']);
        Route::get('/{organ}/summary', [TimelineController::class, 'summary']);
        Route::post('/{organ}/refresh', [TimelineController::class, 'refresh']);
    });
});
