<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function () {
    Route::group(['middleware' => ['guest']], function () {
        Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    });

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
        Route::post('/change-password', [AuthenticatedSessionController::class, 'changePassword']);
    });
});
