<?php

use App\Http\Controllers\Api\V1\ChargeController;
use App\Http\Controllers\Api\V1\TokenController;
use App\Http\Controllers\Api\V1\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('tokens', [TokenController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('charges', [ChargeController::class, 'store']);
        Route::get('transactions', [TransactionController::class, 'index']);
    });
});
