<?php

use App\Http\Controllers\CallbackController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\PaymentOptionController;



Route::get('payments/airtel/callback', [CallbackController::class, 'airtel']);


Route::middleware(['auth:sanctum'])->group(function () {


    Route::get('/cart', [CartController::class, 'index']); //get all the contents on cart with items and sub total, total
    Route::post('/cart', [CartController::class, 'store']); //this one adds item to cart
    Route::patch('/cart/{id}', [CartController::class, 'update']); //increment or decrement, by taking the quantity field and do the magic.
    Route::put('/cart/{cartId}/product/{productId}/increment', [CartController::class, 'add']);
    Route::delete('/cart/{cartId}/product/{productId}/decrement', [CartController::class, '']);


});

// Route::post('/checkout'); //now transfer the cart to orders,address and payment methods.
// Route::get('/orders'); // list of buyers' orders
// Route::get('/orders/{id}'); //view details of a single order items, address, status , shipment
// Route::put('/orders/{id}/cancel'); //cancel an order before shipped.
// Route::post('/orders/{id}/refund'); //refund the money

// Route::resource('/payment/options', PaymentOptionController::class);
// //resource for managing the payment options.

// Route::post('/orders/{id}/pay'); //initiate payment for 
// Route::get('/orders/{id}/payment');


// Route::get('/payments'); //get buyers payment history
