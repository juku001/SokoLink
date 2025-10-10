<?php

use App\Http\Controllers\ExportFileReportController;
use App\Http\Controllers\GraphController;
use App\Http\Controllers\OnlinePerformanceReportController;
use App\Http\Controllers\PdfFileReportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SellerOverviewController;


//here
Route::get('/admin/reports/revenue/analysis', [SellerOverviewController::class, 'revenueAnalysis']);
Route::get('/admin/reports/marketplace/health', [SellerOverviewController::class, 'marketplaceHealth']);
//adn here

Route::prefix('reports')->group(function () {

    Route::get('/export/excel', [ExportFileReportController::class, 'csv']);
    Route::get('/export/pdf', [ExportFileReportController::class, 'pdf']);



    Route::get('/seller/top-categories', [SellerOverviewController::class, 'topCategories']);
    Route::get('/sales/performance', [ReportController::class, 'salesPerformance']);
    Route::get('/profit/analysis', [ReportController::class, 'profitAnalysis']);
    Route::get('/inventory', action: [ReportController::class, 'inventory']);
    Route::get('/credit/score', [ReportController::class, 'creditScore']);

    Route::prefix('/online/performance')->group(function () {

        Route::get('/stats', [OnlinePerformanceReportController::class, 'index']);
        Route::get('/store/activity', [OnlinePerformanceReportController::class, 'activity']);
        Route::get('/conversion/rate', [OnlinePerformanceReportController::class, 'conversion']);
        Route::get('/top/performing', [OnlinePerformanceReportController::class, 'topPerformance']);
        Route::get('/products', [OnlinePerformanceReportController::class, 'product']);

    });

});