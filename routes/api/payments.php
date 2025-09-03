<?php


use App\Http\Controllers\CallbackController;



Route::get('payments/airtel/callback', [CallbackController::class, 'airtel']);