<?php

use App\Http\Controllers\AdminMerchantController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminUserManagementController;
use App\Http\Controllers\CustomerManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SystemSettingController;



Route::middleware(['auth:sanctum', 'user.type:super_admin'])->group(function () {

    Route::prefix('/dashboard/admin')->group(function () {
        Route::get('/customers', [DashboardController::class, 'adminCustomerManagement']);
        Route::get('/payments', [DashboardController::class, 'adminPayments']);
        Route::get('/merchants',[DashboardController::class, 'merchants']);
        Route::get('/platform/health', [DashboardController::class, 'platformHealth'])->middleware('response.time');
    });

    Route::get('/admin/performing/merchants',[AdminMerchantController::class,'top']);


    Route::prefix('admin/reports')->group(function () {
        Route::get('/sales', [AdminReportController::class, 'sales']);
        Route::get('/user/growth', [AdminReportController::class, 'users']);
        Route::get('/revenue', [AdminReportController::class, 'revenue']);
        Route::get('/marketplace', [AdminReportController::class, 'marketplace']);
    });


    Route::get('/admin/merchants', [AdminMerchantController::class, 'index']);
    Route::get('/admin/merchants/{id}', [AdminMerchantController::class, 'show']);



    Route::prefix('/admin/payments')->group(function () {
        Route::get('/', [AdminPaymentController::class, 'index']);
        Route::get('/{id}', [AdminPaymentController::class, 'index']);
    });

    Route::get('admin/customers', [CustomerManagementController::class, 'index']);
    Route::get('admin/customers/{id}', [CustomerManagementController::class, 'show']);

    Route::resource('manage/users', AdminUserManagementController::class);
    Route::put('/manage/users/{id}/status', [AdminUserManagementController::class, 'status'])->name('');

    Route::get('/system_settings', [SystemSettingController::class, 'index']);
    Route::post('/system_settings', [SystemSettingController::class, 'update']);

});

//platform health (stats and percentage),
//service status
//recent alerts



//platform overview stats
//10 recent plaform activity (it should be all, but limited on reqsuest)