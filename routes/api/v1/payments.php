<?php

use App\Http\Controllers\CallbackController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PaymentOptionController;



Route::post('payments/airtel/callback', [CallbackController::class, 'airtel']);


Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/carts', [CartController::class, 'index']); //get all the contents on cart with items and sub total, total
    Route::post('/carts', [CartController::class, 'stor`e']); //this one adds item to cart
    Route::delete('/carts/{id}', [CartController::class, 'destroy']); //delete add item from cart
    Route::patch('/carts/{id}', [CartController::class, 'update']); //increment or decrement, by taking the quantity field and do the magic.
    Route::post('/carts/{cartId}/product/{productId}/increment', [CartController::class, 'add']);
    Route::delete('/carts/{cartId}/product/{productId}/decrement', [CartController::class, 'remove']);

    Route::post('/checkout', [PaymentController::class, 'checkout']); //now transfer the cart to orders,address and payment methods.


 
    Route::post('/payment/process', [PaymentController::class, 'initiate']); //initiate payment for
    
    Route::get('/payments', [PaymentController::class, 'index']); //get buyers payment history


});



Route::middleware(['auth:sanctum', 'user.type:super_admin'])->group(function () {

    Route::get('/payment/methods/all', [PaymentMethodController::class, 'all']);
    Route::post('/payment/methods/{id}/status', [PaymentMethodController::class, 'status']);

});
Route::resource('/payment/methods', PaymentMethodController::class);
Route::resource('/payment/options', PaymentOptionController::class);



Route::post('/testing/payment',[PaymentController::class, 'testing']);