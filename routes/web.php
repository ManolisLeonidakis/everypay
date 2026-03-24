<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard', [
            'name' => auth()->user()->name,
            'api_email' => config('services.merchant_api.email'),
            'api_password' => config('services.merchant_api.password'),
        ]);
    })->name('dashboard');

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/transactions', [DashboardController::class, 'index'])->name('transactions');
});
