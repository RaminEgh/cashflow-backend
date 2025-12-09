<?php

use App\Helpers\Helper;
use App\Http\Controllers\Api\V1\Common\UploadController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::post('/debug', function () {
    return response()->json(['ok' => true]);
});

// Download and display routes are public (authorization handled in controller)
Route::group(['prefix' => 'upload'], function () {
    Route::get('/{upload:slug}/download', [UploadController::class, 'download'])->name('upload.download');
    Route::get('/{upload:slug}/display', [UploadController::class, 'display'])->name('upload.display');
});

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('/user', function () {
        return Helper::successResponse('', auth()->user());
    });

    require __DIR__.'/admin.php';
    require __DIR__.'/organ.php';

    Route::group(['prefix' => 'upload'], function () {
        Route::get('/', [UploadController::class, 'index']);
        Route::post('/', [UploadController::class, 'store']);
        Route::get('/{upload}', [UploadController::class, 'show']);
    });

});
