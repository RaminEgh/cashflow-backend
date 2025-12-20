<?php

use App\Http\Controllers\HorizonLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Horizon login routes (outside /horizon/ to avoid Horizon middleware)
// Allow access even if authenticated via API token (they need web session for Horizon)
Route::get('/horizon-auth/login', [HorizonLoginController::class, 'showLoginForm'])->name('horizon.login.show');
Route::post('/horizon-auth/login', [HorizonLoginController::class, 'login'])->name('horizon.login');
