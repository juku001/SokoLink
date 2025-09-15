<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StoreController;


Route::resource('/categories', CategoryController::class); //these are the categories resource
Route::get('/categories/{id}/stores', [CategoryController::class, 'stores']); //this is for getting all the stores in that category 
Route::get('/categories/{id}/children', [CategoryController::class, 'children']);

Route::get('/dashboard/seller', [DashboardController::class, 'seller']); //this is for the stats for the seller dashboard
Route::get('/dashboard/products', [DashboardController::class, 'products']);

Route::get('/search'); //get list of stores or products or categories searched by name

Route::resource('/stores', StoreController::class);
Route::get('/stores/{id}/list', [StoreController::class, 'list']); //only the online stores for one specific user.
Route::get('/stores/all', [StoreController::class, 'all'])->middleware('user.type:super_admin'); //all the online stores.

Route::resource('/products', ProductController::class);
Route::get('/products/all', [ProductController::class, 'all'])->middleware('user.type:super_admin'); //this is getting all the products regardless of the stores


Route::patch('/products/{id}/online', [ProductController::class, 'status']);//this is for changint the online toggle for the product 
Route::post('/products/bulk-upload', [ProductController::class, 'bulk']); //uploading products from an excel file

Route::get('/products/{id}/reviews', [ReviewController::class, 'products']); // getting all the product reviews
Route::post('/products/{id}/reviews', [ReviewController::class, 'storeProductReview']); //adding the product reviews

Route::get('/stores/{id}/products', [ProductController::class, 'stores']); //getting all the products of that particular store, filter by name and category
Route::patch('/stores/{id}/online', [StoreController::class, 'status']); //this is for changint the online toggle for the store
Route::get('/stores/{id}/reviews', [ReviewController::class, 'stores']); // this is for getting all the reviews of a specific store
Route::post('/stores/{id}/reviews', [ReviewController::class, 'storeStoreReview']);// thsi si for reviewing on a specific store
Route::post('/stores/{id}/follow', [StoreController::class, 'followStore']); // this is for following and unfoollowing for the store.
Route::get('/stores/following', [StoreController::class, 'following']); //this is for the buyer to get list of stores they follow

