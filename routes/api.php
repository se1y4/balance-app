<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BalanceController;

Route::middleware('api')->group(function () {
    Route::post('/deposit', [BalanceController::class, 'deposit']);
    Route::post('/withdraw', [BalanceController::class, 'withdraw']);
    Route::post('/transfer', [BalanceController::class, 'transfer']);
    Route::get('/balance/{user_id}', [BalanceController::class, 'balance']);
});