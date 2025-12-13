<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeaturedStoreController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\StoreFollowingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SellerOverviewController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SuccessStoryController;





Route::get('/success-stories', [SuccessStoryController::class, 'index']);
Route::post('/success-stories', [SuccessStoryController::class, 'store'])->middleware(['auth:sanctum', 'user.type:buyer']);
Route::middleware(['auth:sanctum', 'user.type:super_admin'])->group(function () {
    Route::get('/success-stories/all', [SuccessStoryController::class, 'all']);
    Route::patch('/success-stories/{id}', [SuccessStoryController::class, 'update']);
});


Route::resource('/categories', CategoryController::class);
Route::get('/categories/{id}/stores', [CategoryController::class, 'stores']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/categories/{id}/children', [CategoryController::class, 'children']);
});
Route::get('/search', [SearchController::class, 'index']);

Route::prefix('/stores/{id}')->group(function () {

    Route::get('/products', [ProductController::class, 'stores']);
    Route::get('/reviews', [ReviewController::class, 'stores']);
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/reviews', [ReviewController::class, 'storeStoreReview']);
        Route::get('/follows', [StoreFollowingController::class, 'index'])->middleware('user.type:seller');
        Route::patch('/follows', [StoreFollowingController::class, 'update'])
            ->middleware('user.type:buyer');
    });

    Route::get('/detailed', [StoreController::class, 'detailed']);
    Route::get('/online-status', [StoreController::class, 'status']);
    Route::patch('/online-status', [StoreController::class, 'updateStatus'])->middleware(['auth:sanctum', 'user.type:seller']);

})->whereNumber('id');

Route::middleware(['auth:sanctum', 'user.type:seller'])->group(function () {

    Route::get('/stores/active-store', [StoreController::class, 'active']);
    Route::patch('/stores/active-store', [StoreController::class, 'updateActive']);

});
Route::get('/stores/featured', [FeaturedStoreController::class, 'index']);
Route::put('/stores/featured', [FeaturedStoreController::class, 'update']);

Route::get('/stores/seller', [StoreController::class, 'sellers'])->middleware(['auth:sanctum', 'user.type:seller']);
Route::get('/stores/all', [StoreController::class, 'all']);
Route::get('/stores/{slug}', [StoreController::class, 'storesByslug'])
    ->where('slug', '^(?!\d+$)[A-Za-z0-9-]+$');
Route::resource('/stores', StoreController::class);
Route::get('/products/{id}/reviews', [ReviewController::class, 'products']);
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/products/{id}/reviews', [ReviewController::class, 'storeProductReview']);

    Route::middleware('user.type:seller')->group(function () {

        Route::get('/products/inventory', [InventoryController::class, 'index']); // list of all products with their stock balance
        Route::get('/products/{id}/inventory', [InventoryController::class, 'show']); //get the stock balance of one specific product 
        Route::patch('/products/{id}/inventory', [InventoryController::class, 'update']); //this is for adjustign the stock amount 
        Route::get('/products/{id}/inventory/balance', [InventoryController::class, 'balance']); //get only the balance of a particular product 

        Route::post('/products/{id}/add', [InventoryController::class, 'add']);
        Route::delete('/products/{id}/deduct', [InventoryController::class, 'deduct']);
        Route::patch('/products/{id}/online', [ProductController::class, 'status']);
        Route::patch('/products/{id}/online-status', [ProductController::class, 'online']);
        Route::post('/products/excel', [ProductController::class, 'bulk']);

    });

});
Route::get('/products/all', [ProductController::class, 'all'])->middleware(['auth:sanctum', 'user.type:super_admin']);
Route::get('/products/{id}/detailed', [ProductController::class, 'detailed']);
Route::resource('products', ProductController::class);




