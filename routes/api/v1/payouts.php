<?php

use App\Http\Controllers\EscrowController;
use App\Http\Controllers\PayoutController;


Route::middleware(['auth:sanctum'])->group(function () {

    Route::middleware('user.type:seller')->group(function () {

        Route::get('/balance/merchant', [EscrowController::class, 'merchant']); //get all total balance for the merchant
        Route::get('/merchant/store', [EscrowController::class, 'store']); //get all total balance for the merchant store

    });

    Route::get('/escrows', [EscrowController::class, 'index'])->middleware('user.type:super_admin'); //get list of all merchants and their balance. 

    Route::middleware('user.type:seller')->group(function () {
        Route::get('/payouts', [PayoutController::class, 'index']);
        Route::post('/payouts', [PayoutController::class, 'store']);
        Route::get('/payouts/{id}', [PayoutController::class, 'show'])->whereNumber('id');
    });
    Route::get('/payouts/all', [PayoutController::class, 'all'])->middleware('user.type:super_admin');
});