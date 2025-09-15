<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GroupContactController;
use App\Http\Controllers\GroupController;



Route::middleware(['auth:sanctum'])->group(function () {

    Route::middleware(['user.type:seller'])->group(function () {
        Route::get('/dashboard/contacts', [DashboardController::class, 'contacts']);

        Route::resource('/contacts', ContactController::class);

        Route::resource('/groups', GroupController::class);

        Route::post('/contact/group/assign', [GroupContactController::class, 'store']); // assign group to contact
        Route::post('/contact/group/remove', [GroupContactController::class, 'destroy']); //remove contact from group
    });


    Route::post('/feedbacks', [FeedbackController::class, 'store']);
    Route::get('/feedbacks', [FeedbackController::class, 'index'])->middleware('user.type:super_admin');
});



