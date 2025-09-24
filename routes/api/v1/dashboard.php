<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SellerOverviewController;


Route::middleware('auth:sanctum')->group(function () {



    Route::middleware('user.type:seller')->group(function () {

        Route::prefix('/dashboard')->group(function () {
            Route::get('/overview/recent-sales', [SellerOverviewController::class, 'recentSales']);
            Route::get('/overview/low-stock', [SellerOverviewController::class, 'lowStock']);
            Route::get('/overview/stats', [SellerOverviewController::class, 'index']);

            Route::get('/contacts/stats', [DashboardController::class, 'contacts']);
            Route::get('/academy/stats', [DashboardController::class, 'academy']);
            Route::get('/sales/stats', [SalesController::class, 'dashboard']);
            Route::get('/expenses/stats', [DashboardController::class, 'expenses']);
            Route::get('/products/stats', [DashboardController::class, 'products']);
            Route::get('/reports/stats', [DashboardController::class, 'reports']);
        });


    });


    Route::middleware('user.type:super_admin')->group(function () {
        Route::prefix('/dashboard/admin')->group(function () {
            Route::get('/customers', [DashboardController::class, 'adminCustomerManagement']);
            Route::get('/payments', [DashboardController::class, 'adminPayments']);
            Route::get('/merchants', [DashboardController::class, 'merchants']);
            Route::get('/platform/health', [DashboardController::class, 'platformHealth'])->middleware('response.time');
        });

    });
});