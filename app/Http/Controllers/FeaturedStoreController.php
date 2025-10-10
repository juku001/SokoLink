<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class FeaturedStoreController extends Controller implements HasMiddleware
{

    public static function middleware()
    {
        return [
            new Middleware(['auth:sanctum', 'user.type:super_admin'], only: ['update'])
        ];
    }


    /**
     * @OA\Get(
     *     path="/stores/featured",
     *     summary="List all featured online stores",
     *     description="Returns a list of stores that are currently featured online.  
     *                  If a valid bearer token is provided, each store will include an `is_follow` flag
     *                  indicating whether the authenticated user follows that store.",
     *     tags={"Stores"},
     *     @OA\Response(
     *         response=200,
     *         description="List of featured online stores",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Featured Store listings"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=15),
     *                     @OA\Property(property="name", type="string", example="City Electronics"),
     *                     @OA\Property(property="subtitle", type="string", example="Best gadgets in town"),
     *                     @OA\Property(property="thumbnail", type="string", example="https://example.com/images/store-thumb.jpg"),
     *                     @OA\Property(property="address", type="string", example="123 Main Street"),
     *                     @OA\Property(property="region", type="string", nullable=true, example="Dar es Salaam"),
     *                     @OA\Property(property="category", type="string", nullable=true, example="Vifaa vya umeme"),
     *                     @OA\Property(property="country", type="string", nullable=true, example="Tanzania"),
     *                     @OA\Property(property="rating", type="number", format="float", example=4.7),
     *                     @OA\Property(property="reviews_count", type="integer", example=23),
     *                     @OA\Property(property="is_follow", type="boolean", example=false)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {

        $token = $request->bearerToken();
        $user = null;

        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $user = $accessToken->tokenable;
            }
        }

        // base query
        $query = Store::with('category', 'region.country', 'reviews')
            ->where('is_online', true)->where('is_featured', true);

        $stores = $query->get()->map(function ($store) use ($user) {
            return [
                'id' => $store->id,
                'name' => $store->name,
                'subtitle' => $store->subtitle,
                'thumbnail' => $store->thumbnail,
                'address' => $store->address,
                'category' => optional($store->category)->name,
                'region' => optional($store->region)->name,
                'country' => optional(optional($store->region)->country)->name,
                'rating' => $store->rating_avg,
                'reviews_count' => $store->reviews->count(),
                'is_follow' => $user
                    ? $store->followers()->where('buyer_id', $user->id)->exists()
                    : false,
            ];
        });

        return ResponseHelper::success($stores, 'List of featured stores.');
    }



    /**
     * @OA\Put(
     *     path="/stores/featured",
     *     tags={"Stores"},
     *     summary="Toggle a store's featured status",
     *     description="Allows an admin or super admin to mark a store as featured or unfeatured. This endpoint toggles the current status.",
     *     security={{"bearerAuth": {}}},
     * 
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"store_id"},
     *             @OA\Property(
     *                 property="store_id",
     *                 type="integer",
     *                 example=12,
     *                 description="The ID of the store to be featured or unfeatured"
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="Store featured status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Store featured status updated."),
     *             
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=422),
     *             @OA\Property(property="message", type="string", example="Failed to validate fields."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="store_id", type="array",
     *                     @OA\Items(type="string", example="The selected store_id is invalid.")
     *                 )
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=403),
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only admins can feature stores.")
     *         )
     *     )
     * )
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|numeric|exists:stores,id'
        ], [
            'store_id.exists' => 'Unknown store'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields.', 422);
        }

        $store = Store::find($request->store_id);
        $store->is_featured = !$store->is_featured;
        $store->save();

        return ResponseHelper::success([], 'Store featured status updated.');
    }

}
