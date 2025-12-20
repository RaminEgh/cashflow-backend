<?php

use App\Http\Controllers\HorizonLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Horizon login routes (outside /horizon/ to avoid Horizon middleware)
Route::middleware('guest')->group(function () {
    Route::get('/horizon-auth/login', [HorizonLoginController::class, 'showLoginForm'])->name('horizon.login.show');
    Route::post('/horizon-auth/login', [HorizonLoginController::class, 'login'])->name('horizon.login');

    // Fallback login route for Laravel's default redirect
    Route::get('/login', function () {
        return redirect()->route('horizon.login.show');
    })->name('login');
});
