<?php

use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\OrderController;





Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/{id}', [OrderController::class, 'show'])->whereNumber('id'); //view details of a single order items, address, status , shipment
Route::get('/orders/status/{id}', [OrderController::class, 'status']); //getting all the status of that particular order
Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']); //cancel an order before shipped.


Route::middleware(['user.type:seller'])->group(function () {
    Route::get('/orders/shipping', [DeliveryController::class, 'index']); //this is for sellers to get list of orders on the shipping. 
    Route::put('/orders/shipping/{id}', [DeliveryController::class, 'update']);//this is for the sellers to update the shipping status for the order.
});


Route::put('/orders/delivered', [DeliveryController::class, 'delivered']); //buyer confirms order deliverd for a product.



//sellers view orders 
