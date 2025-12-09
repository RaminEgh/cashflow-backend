<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Web login route for Horizon dashboard access
Route::get('/login', function () {
    return view('horizon-login');
})->middleware('guest')->name('login');

Route::post('/login', [AuthenticatedSessionController::class, 'webLogin'])->middleware('guest');
