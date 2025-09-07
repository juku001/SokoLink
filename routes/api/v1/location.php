<?php



Route::get('/countries');
Route::get('/countries/{id}/regions');
Route::get('/countries/{code}/regions');

Route::post('/countries');
Route::post('/regions');

Route::put('/countries/{id}');
Route::put('/regions/{id}');


Route::delete('/countries/{id}');
Route::delete('/regions/{id}');