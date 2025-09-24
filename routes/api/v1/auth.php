<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleSignInController;
use App\Http\Controllers\LogInController;
use App\Http\Controllers\LogInProviderController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\UserController;


Route::get('/', [AuthController::class, 'unauthorized'])->name('login');
Route::get('/is_auth', [AuthController::class, 'authorized'])->middleware('auth:sanctum');


Route::prefix('auth')->group(function () {

    Route::post('/verified/email', [AuthController::class, 'verifiedEmail']);
    Route::post('/verified/mobile', [AuthController::class, 'verifiedMobile']);

    Route::post('/register', [RegistrationController::class, 'index']); // this is for the customer to register and for super admin to register admin

    Route::post('/verify/email', [RegistrationController::class, 'verify'])->middleware('auth:sanctum');

    Route::post('/login', [LogInController::class, 'index']); //login in via mobile
    
    Route::post('/verify/otp', [LogInController::class, 'verify']);//verify mobile number otp



    Route::post('/login/email', [LogInController::class, 'email']); //login in via email
    Route::post('/login/google', [GoogleSignInController::class, 'login']);// login via google

    Route::post('/login/google/callback', [GoogleSignInController::class, 'callback']);


    Route::post('/password/forgot', [PasswordController::class, 'index']); //send otp for password changing
    Route::post('/password/reset', [PasswordController::class, 'store']); //verfiy otp , new password

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/be/seller', [AuthController::class, 'seller']);
        Route::put('/be/seller',[AuthController::class, 'updateSeller']);

        Route::post('/password/update', [PasswordController::class, 'update']); //update existing when logged in.

        Route::post('/provider/add', [LogInProviderController::class, 'store']);//adding login provider
        Route::patch('/provider/change', [LogInProviderController::class, 'update']);
        Route::delete('/provider/remove', [LogInProviderController::class, 'destroy']);//removing login provider

        Route::get('/me', [UserController::class, 'show']); //this is for the logged in user
        Route::post('/logout', [AuthController::class, 'destroy']); //logging out from any of the provider

    });

});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserController::class, 'index']); //get all the users filter by roless
    Route::patch('/users/{id}', [UserController::class, 'update']); //update the user status
    Route::put('/profile/update', [UserController::class, 'profileUpdate']);
    Route::put('/admin/users/{id}/profile', [UserController::class, 'profileUpdateByAdmin'])->middleware('user.type:super_admin');
});