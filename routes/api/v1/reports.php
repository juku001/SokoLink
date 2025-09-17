<?php

use App\Http\Controllers\OnlinePerformanceReportController;
use App\Http\Controllers\ReportController;

Route::prefix('reports')->group(function () {

    Route::get('/sales/performance', [ReportController::class, 'salesPerformance']);
    Route::get('/profit/analysis', [ReportController::class, 'profitAnalysis']);

    Route::get('/inventory', [ReportController::class, 'inventory']);

    Route::get('/credit/score', [ReportController::class, 'creditScore']);

    Route::prefix('/online/performance')->group(function () {

        Route::get('/stats', [OnlinePerformanceReportController::class, 'index']);
        Route::get('/store/activity', [OnlinePerformanceReportController::class, 'activity']);
        Route::get('/conversion/rate', [OnlinePerformanceReportController::class, 'conversion']);
        Route::get('/top/performing', [OnlinePerformanceReportController::class, 'topPerformance']);
        Route::get('/products', [OnlinePerformanceReportController::class, 'product']);
        
    });

});