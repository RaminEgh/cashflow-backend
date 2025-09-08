<?php

use App\Helpers\Helper;
use App\Http\Controllers\Api\V1\UploadController;
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

});

