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
    Route::post('/carts', [CartController::class, 'store']); //this one adds item to cart
    Route::patch('/carts/{id}', [CartController::class, 'update']); //increment or decrement, by taking the quantity field and do the magic.
    Route::post('/carts/{cartId}/product/{productId}/increment', [CartController::class, 'add']);
    Route::delete('/carts/{cartId}/product/{productId}/decrement', [CartController::class, 'remove']);

    Route::post('/checkout', [PaymentController::class, 'checkout']); //now transfer the cart to orders,address and payment methods.


    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show'])->whereNumber('id'); //view details of a single order items, address, status , shipment
    Route::get('/orders/status/{id}', [OrderController::class, 'status']); //getting all the status of that particular order
    Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']); //cancel an order before shipped.

    Route::post('/payment/process', [PaymentController::class, 'initiate']); //initiate payment for
    Route::get('/payments', [PaymentController::class, 'index']); //get buyers payment history

    Route::middleware(['user.type:seller'])->group(function () {
        Route::get('/orders/shipping', [DeliveryController::class, 'index']); //this is for sellers to get list of orders on the shipping. 
        Route::put('/orders/shipping/{id}', [DeliveryController::class, 'update']);//this is for the sellers to update the shipping status for the order.
    });

    // Route::put('/orders/shipping/', [DeliveryController::class, 'shipping'])->middleware('user.type:seller');
    Route::put('/orders/delivered', [DeliveryController::class, 'delivered']);
});



Route::middleware(['auth:sanctum', 'user.type:super_admin'])->group(function () {

    Route::get('/payment/methods/all', [PaymentMethodController::class, 'all']);
    Route::post('/payment/methods/{id}/status', [PaymentMethodController::class, 'status']);

});
Route::resource('/payment/methods', PaymentMethodController::class);
Route::resource('/payment/options', PaymentOptionController::class);
