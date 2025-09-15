<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{


    /**
     * @OA\Get(
     *     path="/products/{id}/reviews",
     *     tags={"Products"},
     *     summary="Get reviews for a product",
     *     description="Retrieve all reviews for a specific product, optionally including reviewer info.",
     *     operationId="getProductReviews",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Reviews retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reviews for product"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Review")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function products(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return ResponseHelper::error([], "Product not found", 404);
        }

        $reviews = $product->reviews()->with('user')->get(); // optional: include reviewer info

        return ResponseHelper::success($reviews, "Reviews for product");
    }





    /**
     * @OA\Post(
     *     path="/products/{id}/reviews",
     *     tags={"Products"},
     *     summary="Add a review to a product",
     *     description="Submit a rating and review for a specific product. Users can only review a product once.",
     *     operationId="storeProductReview",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product to review",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"rating","review"},
     *             @OA\Property(property="rating", type="integer", enum={1,2,3,4,5}, example=5, description="Rating from 1 to 5"),
     *             @OA\Property(property="review", type="string", example="Great product!", description="Textual review")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Review added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Review added successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Review")
     *         )
     *     ),
     *
     *     @OA\Response(response=409, description="User has already reviewed this product"),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
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




    /**
     * @OA\Get(
     *     path="/stores/{id}/reviews",
     *     tags={"Stores"},
     *     summary="Get reviews for a store",
     *     description="Retrieve all reviews for a specific store, optionally including reviewer info.",
     *     operationId="getStoreReviews",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the store",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Reviews retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reviews for store"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Review")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Store not found"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function stores(string $id)
    {
        $store = Store::find($id);

        if (!$store) {
            return ResponseHelper::error([], "store not found", 404);
        }

        $reviews = $store->reviews()->with('user')->get(); // optional: include reviewer info

        return ResponseHelper::success($reviews, "Reviews for store");
    }





    /**
     * @OA\Post(
     *     path="/stores/{id}/reviews",
     *     tags={"Stores"},
     *     summary="Add a review to a store",
     *     description="Submit a rating and review for a specific store. Users can only review a store once.",
     *     operationId="storeStoreReview",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the store to review",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"rating","review"},
     *             @OA\Property(property="rating", type="integer", enum={1,2,3,4,5}, example=5, description="Rating from 1 to 5"),
     *             @OA\Property(property="review", type="string", example="Excellent service!", description="Textual review")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Review added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Review added successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Review")
     *         )
     *     ),
     *
     *     @OA\Response(response=409, description="User has already reviewed this store"),
     *     @OA\Response(response=404, description="Store not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
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
