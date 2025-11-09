<?php

use App\Helpers\Helper;
use App\Http\Controllers\Api\V1\Admin\SettingController;
use App\Http\Controllers\Api\V1\Admin\TimelineController;
use App\Http\Controllers\Api\V1\ParsianTestController;
use App\Http\Controllers\Api\V1\Common\UploadController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';

Route::get('/test', function () {
    return Helper::successResponse('', ['this is ' => 'test']);
});

Route::post('/debug', function () {
    return response()->json(['ok' => true]);
});

Route::get('/test-parsian-token', function () {
    // اطلاعات دقیق طبق مستندات
    $clientId = '4836766166044676016';
    $clientSecret = '6040bf64-bf1e-4285-84ea-68b1614f440d';

    $results = [];

    $url = 'https://sandbox.parsian-bank.ir/oauth2/token';

    try {
        // مهم: باید asForm() باشد برای x-www-form-urlencoded
        $response = \Illuminate\Support\Facades\Http::timeout(15)
            ->withBasicAuth($clientId, $clientSecret)
            ->asForm() // این خط حتماً باید باشد!
            ->post($url, [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

        $results = [
            'url' => $url,
            'status' => $response->status(),
            'success' => $response->successful(),
            'headers' => $response->headers(),
            'body' => $response->json() ?? $response->body(),
        ];
    } catch (\Exception $e) {
        $results = [
            'url' => $url,
            'error' => $e->getMessage(),
            'error_type' => get_class($e),
        ];
    }

    return response()->json([
        'client_id' => $clientId,
        'results' => $results,
    ]);
});

Route::get('/test-parsian', function () {
    $bankFactory = app(\App\Services\Banking\BankAdapterFactory::class);

    try {
        // ساخت adapter برای بانک پارسیان
        $adapter = $bankFactory->make('parsian');

        // تنظیم اطلاعات حساب
        $adapter->setAccount([
            'accountNumber' => '85000005464007', // شماره حساب شما
        ]);

        // دریافت موجودی کامل
        $balanceInfo = $adapter->getAccountBalance();

        return response()->json([
            'success' => true,
            'data' => $balanceInfo,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
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

// Parsian Bank Test Routes
Route::group(['prefix' => 'parsian'], function () {
    Route::get('/test', [ParsianTestController::class, 'testConnection']);
    Route::post('/balance', [ParsianTestController::class, 'getBalance']);
    Route::post('/balance/simple', [ParsianTestController::class, 'getSimpleBalance']);
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

    require __DIR__ . '/admin.php';
    require __DIR__ . '/organ.php';

    Route::group(['prefix' => 'upload'], function () {
        Route::get('/', [UploadController::class, 'index']);
        Route::post('/', [UploadController::class, 'store']);
        Route::get('/{upload}', [UploadController::class, 'show']);
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
