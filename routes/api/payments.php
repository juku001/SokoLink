<?php

use App\Http\Controllers\CallbackController;



Route::prefix('v1')->group(function () {

    Route::get('payments/airtel/callback', [CallbackController::class, 'airtel']);

});