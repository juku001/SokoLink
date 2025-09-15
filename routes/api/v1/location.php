<?php

use App\Http\Controllers\LocationController;



Route::get('/countries', [LocationController::class, 'countries']);
Route::get('/countries/{id}/regions', [LocationController::class, 'regionsById'])
    ->whereNumber('id');
Route::get('/countries/{code}/regions', [LocationController::class, 'regionsByCode'])
    ->whereAlpha('code');

Route::post('/countries', [LocationController::class, 'addCountry']);
Route::post('/regions', [LocationController::class, 'addRegion']);

Route::put('/countries/{id}', [LocationController::class, 'updateCountry']);
Route::put('/regions/{id}', [LocationController::class, 'updateRegion']);


Route::delete('/countries/{id}', [LocationController::class, 'deleteCountry']);
Route::delete('/regions/{id}', [LocationController::class, 'deleteRegion']);