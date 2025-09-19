<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseTypeController;
use App\Http\Controllers\SalesController;




Route::resource('/expense/types', ExpenseTypeController::class);

Route::middleware(['auth:sanctum', 'user.type:seller'])->group(function () {
    Route::post('/sales', [SalesController::class, 'store']);
    Route::post('/sales/bulk', [SalesController::class, 'storeBulk']);
    Route::get('/sales', [SalesController::class, 'index']);
    Route::get('/sales/{id}', [SalesController::class, 'show']);
    Route::get('/dashboard/sales', [SalesController::class, 'dashboard']);

    Route::get('/dashboard/expenses', [DashboardController::class, 'expenses']);
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::patch('/expenses', [ExpenseController::class, 'update']);
});


