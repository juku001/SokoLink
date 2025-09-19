<?php

use App\Http\Controllers\AcademyController;
use App\Http\Controllers\AcademyLessonController;
use App\Http\Controllers\DashboardController;


Route::middleware(['auth:sanctum'])->group(function () {


    Route::get('/dashboard/seller/academy', [DashboardController::class, 'academy']);
    Route::resource('/academy', AcademyController::class);
    Route::get('/academy/lessons/{id}', [AcademyLessonController::class, 'show']); //getting a video from an academy group

    Route::middleware(['user.type:super_admin'])->group(function () {

        Route::post('/academy/{id}/lessons', [AcademyLessonController::class, 'store']); //adding a video to an academy group
        Route::put('/academy/lessons/{id}', [AcademyLessonController::class, 'update']); //update a video from an academy group
        Route::delete('/academy/lessons/{id}', [AcademyLessonController::class, 'destroy']); //deleting a video from an academy group

    });
});