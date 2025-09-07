<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{


    public function products(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return ResponseHelper::error([], "Product not found", 404);
        }

        $reviews = $product->reviews()->with('user')->get(); // optional: include reviewer info

        return ResponseHelper::success($reviews, "Reviews for product");
    }




    public function storeProductReview(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|in:1,2,3,4,5',
            'review' => 'required|string'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                "Failed to validate fields.",
                422
            );
        }

        $product = Product::find($id);

        if (!$product) {
            return ResponseHelper::error([], "Product not found", 404);
        }

        // Optional: prevent duplicate reviews by the same user
        if ($product->reviews()->where('user_id', auth()->id())->exists()) {
            return ResponseHelper::error([], "You have already reviewed this product", 409);
        }

        $data = $validator->validated();
        $review = $product->reviews()->create([
            'user_id' => auth()->id(),
            'rating' => $data['rating'],
            'review' => $data['review'],
            'is_verified_purchase' => true
        ]);

        return ResponseHelper::success(
            $review,
            "Review added successfully",
            201
        );
    }




    public function stores(string $id)
    {
        $store = Store::find($id);

        if (!$store) {
            return ResponseHelper::error([], "store not found", 404);
        }

        $reviews = $store->reviews()->with('user')->get(); // optional: include reviewer info

        return ResponseHelper::success($reviews, "Reviews for store");
    }




    public function storeStoreReview(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|in:1,2,3,4,5',
            'review' => 'required|string'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                "Failed to validate fields.",
                422
            );
        }

        $store = Store::find($id);

        if (!$store) {
            return ResponseHelper::error([], "store not found", 404);
        }

        // Optional: prevent duplicate reviews by the same user
        if ($store->reviews()->where('user_id', auth()->id())->exists()) {
            return ResponseHelper::error([], "You have already reviewed this store", 409);
        }

        $data = $validator->validated();
        $review = $store->reviews()->create([
            'user_id' => auth()->id(),
            'rating' => $data['rating'],
            'review' => $data['review'],
            'is_verified_purchase' => true
        ]);

        return ResponseHelper::success(
            $review,
            "Review added successfully",
            201
        );
    }

}
