<?php


Route::get('/dashboard/admin'); //getting admin stats and operational metrics

Route::get('dashboard/compliance');

Route::get('/merchants/top'); //top five performing mechants.

Route::post('/documents/verification'); //approve documents by the seller

Route::get('/customers/all'); //get list of customers and purchases 