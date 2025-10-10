<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Seller;
use App\Models\Store;
use App\Models\StoreVisit;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class StoreController extends Controller implements HasMiddleware
{


    public static function middleware(): array
    {
        return [
            new Middleware(['auth:sanctum', 'user.type:seller,super_admin'], only: [
                'store',
                'update',
                'destroy'
            ]),
        ];
    }


    /**
     * @OA\Get(
     *     path="/stores",
     *     summary="List all online stores",
     *     description="Returns a list of stores that are currently online.  
     *                  If a valid bearer token is provided, each store will include an `is_follow` flag
     *                  indicating whether the authenticated user follows that store.",
     *     tags={"Stores"},
     *     @OA\Response(
     *         response=200,
     *         description="List of online stores",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store listings"),
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
     *                     
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

        // Base query with relations
        $query = Store::with('category', 'region.country', 'reviews');

        // Apply filtering based on user type
        if ($user) {
            switch ($user->role) {
                case 'seller':
                    // Show only stores owned by the seller
                    $query->where('seller_id', $user->id);
                    break;

                case 'admin':
                case 'super_admin':
                    // Show all stores, online or offline — no extra filter
                    break;

                default:
                    // Logged-in buyer or other type — show only online stores
                    $query->where('is_online', true);
                    break;
            }
        } else {
            // Guest user — only online stores
            $query->where('is_online', true);
        }

        // Filter featured stores if requested
        if ($request->has('is_featured') && $request->boolean('is_featured')) {
            $query->where('is_featured', true);
        }

        // Execute query and map the results
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
            ];
        });

        return ResponseHelper::success($stores, 'Store listings');
    }





    /**
     * @OA\Post(
     *     path="/stores",
     *     summary="Create a new store (sellers only)",
     *     description="Allows an authenticated **seller** to create a new store.  
     *                  The authenticated user must have their account name and email set before creating a store.",
     *     tags={"Stores"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","category_id"},
     *                 @OA\Property(property="name", type="string", maxLength=255, example="City Electronics"),
     *                 @OA\Property(property="category_id", type="integer", example=3),
     *                 @OA\Property(property="description", type="string", nullable=true, example="Wide range of electronics and gadgets."),
     *                 @OA\Property(property="subtitle", type="string", maxLength=255, nullable=true, example="Best gadgets in town"),
     *                 @OA\Property(property="thumbnail", type="string", format="binary", nullable=true, description="Store thumbnail image (max 2 MB)"),
     *                 @OA\Property(property="is_online", type="boolean", nullable=true, example=true),
     *                 @OA\Property(property="contact_mobile", type="string", maxLength=20, nullable=true, example="+255700000000"),
     *                 @OA\Property(property="contact_email", type="string", format="email", maxLength=255, nullable=true, example="info@cityelectronics.tz"),
     *                 @OA\Property(property="whatsapp", type="string", maxLength=20, nullable=true, example="+255700000000"),
     *                 @OA\Property(property="shipping_origin", type="string", maxLength=255, nullable=true, example="Dar es Salaam Warehouse"),
     *                 @OA\Property(property="region_id", type="integer", nullable=true, example=5),
     *                 @OA\Property(property="address", type="string", maxLength=255, nullable=true, example="123 Main Street, Dar es Salaam")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Store created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store created successfully"),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Created store object",
     *                 @OA\Property(property="id", type="integer", example=25),
     *                 @OA\Property(property="name", type="string", example="City Electronics"),
     *                 @OA\Property(property="category_id", type="integer", example=3),
     *                 @OA\Property(property="thumbnail", type="string", example="store_thumbnails/abc123.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid user state or database error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Please set your account name and email before adding a store."),
     *             @OA\Property(property="code", type="integer", example=400)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'subtitle' => 'nullable|string|max:255',
            'thumbnail' => 'nullable|image|max:2048',
            'is_online' => 'nullable|boolean',
            'contact_mobile' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:20',
            'shipping_origin' => 'nullable|string|max:255',
            'region_id' => 'nullable|exists:regions,id',
            'address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields.',
                422
            );
        }

        $user = auth()->user();

        if (empty($user->name) || empty($user->email)) {
            return ResponseHelper::error([], 'Please set your account name and email before adding a store.', 400);
        }

        DB::beginTransaction();

        try {
            $sellerId = $user->id;
            $data = $validator->validated();
            $data['seller_id'] = $sellerId;

            if ($request->hasFile('thumbnail')) {
                $path = $request->file('thumbnail')->store('store_thumbnails', 'public');
                $data['thumbnail'] = $path;
            }

            $store = Store::create($data);

            $seller = Seller::where('user_id', $sellerId)->first();
            if ($seller && $seller->active_store === null) {
                $seller->active_store = $store->id;
                $seller->save();
            }

            DB::commit();

            return ResponseHelper::success($store, 'Store created successfully', 201);

        } catch (QueryException $e) {
            DB::rollBack();
            return ResponseHelper::error([], "DB Error: " . $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }



    /**
     * @OA\Patch(
     *     path="/stores/{id}",
     *     summary="Update an existing store",
     *     description="Allows the **authenticated seller** who owns the store to update its details.  
     *                  Only the fields provided in the request will be updated.",
     *     tags={"Stores"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the store to update",
     *         @OA\Schema(type="integer", example=25)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", maxLength=255, nullable=true, example="Updated Store Name"),
     *                 @OA\Property(property="category_id", type="integer", nullable=true, example=4),
     *                 @OA\Property(property="description", type="string", nullable=true, example="Updated store description."),
     *                 @OA\Property(property="subtitle", type="string", maxLength=255, nullable=true, example="Updated subtitle"),
     *                 @OA\Property(property="thumbnail", type="string", format="binary", nullable=true, description="New thumbnail image (max 2 MB)"),
     *                 @OA\Property(property="is_online", type="boolean", nullable=true, example=true),
     *                 @OA\Property(property="contact_mobile", type="string", maxLength=20, nullable=true, example="+255711111111"),
     *                 @OA\Property(property="contact_email", type="string", format="email", maxLength=255, nullable=true, example="newcontact@store.tz"),
     *                 @OA\Property(property="whatsapp", type="string", maxLength=20, nullable=true, example="+255711111111"),
     *                 @OA\Property(property="shipping_origin", type="string", maxLength=255, nullable=true, example="Updated warehouse location"),
     *                 @OA\Property(property="region_id", type="integer", nullable=true, example=6),
     *                 @OA\Property(property="address", type="string", maxLength=255, nullable=true, example="456 New Street, Dodoma")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Store updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store updated successfully"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Updated store object",
     *                 @OA\Property(property="id", type="integer", example=25),
     *                 @OA\Property(property="name", type="string", example="Updated Store Name"),
     *                 @OA\Property(property="thumbnail", type="string", example="store_thumbnails/newimage.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Store not found or not owned by authenticated seller",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Store not found or unauthorized"),
     *             @OA\Property(property="code", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed for one or more fields",
     *         ref="#/components/responses/422"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */


    public function update(Request $request, $id)
    {


        $store = Store::where('id', $id)
            ->where('seller_id', auth()->id())
            ->first();

        if (!$store) {
            return ResponseHelper::error([], 'Store not found or unauthorized', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:stores,name,' . $store->id,
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'subtitle' => 'nullable|string|max:255',
            'thumbnail' => 'nullable|image|max:2048',
            'is_online' => 'nullable|boolean',
            'contact_mobile' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:20',
            'shipping_origin' => 'nullable|string|max:255',
            'region_id' => 'nullable|exists:regions,id',
            'address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields.',
                422
            );
        }

        try {
            $data = $validator->validated();

            // Handle thumbnail upload if provided
            if ($request->hasFile('thumbnail')) {
                // Delete old thumbnail if it exists
                if ($store->thumbnail) {
                    Storage::disk('public')->delete($store->thumbnail);
                }

                // Store the new one
                $path = $request->file('thumbnail')->store('store_thumbnails', 'public');
                $data['thumbnail'] = $path;
            }

            $store->update($data);

            return ResponseHelper::success($store, 'Store updated successfully');

        } catch (Exception $e) {
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/stores/{id}",
     *     summary="Get store details",
     *     description="Returns detailed information about a store, including followers count, average rating, and reviews count.  
     *                  Authentication is optional; if a valid bearer token is provided, `is_follow` indicates whether the user follows this store.",
     *     tags={"Stores"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the store to retrieve",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\SecurityScheme(
     *         securityScheme="optionalBearer",
     *         type="http",
     *         scheme="bearer",
     *         bearerFormat="JWT"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Store details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store details"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=12),
     *                 @OA\Property(property="name", type="string", example="City Electronics"),
     *                 @OA\Property(property="description", type="string", example="Best gadgets in town"),
     *                 @OA\Property(property="followers_count", type="integer", example=150),
     *                 @OA\Property(property="reviews_count", type="integer", example=20),
     *                 @OA\Property(property="category", type="string", example="Electronics"),
     *                 @OA\Property(property="rating_avg", type="number", format="float", example=4.5),
     *                 @OA\Property(property="is_follow", type="boolean", example=false),
     *                 @OA\Property(property="is_online", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Store not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Store not found."),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     )
     * )
     */

    public function show(Request $request, string $id)
    {
        $user = null;
        if ($token = $request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $user = $accessToken->tokenable;
            }
        }

        $isBuyer = $user && $user->role === 'buyer';

        $query = Store::with(['category']) 
            ->withCount(['followers', 'reviews']);

        if (!$user || $isBuyer) {
            $query->where('is_online', true);
        } elseif ($user->role === 'seller') {
            $query->where('seller_id', $user->id);
        }

        $store = $query->find($id);

        if (!$store) {
            return ResponseHelper::error([], 'Store not found.', 404);
        }

        if ($isBuyer) {
            StoreVisit::create([
                'store_id' => $store->id,
                'user_id' => $user->id,
                'session_id' => session()->getId(),
                'ip_address' => $request->ip(),
            ]);
        }

        $isFollow = $isBuyer
            ? $store->followers()->where('buyer_id', $user->id)->exists()
            : false;

        return ResponseHelper::success([
            'id' => $store->id,
            'name' => $store->name,
            'description' => $store->description,
            'followers_count' => $store->followers_count,
            'rating_avg' => $store->rating_avg,
            'reviews_count' => $store->reviews_count,
            'category' => optional($store->category)->name,
            'is_online' => (bool) $store->is_online,
            'is_follow' => $isFollow,
        ], 'Store details');
    }




    /**
     * Remove the specified resource from storage.
     */

    /**
     * @OA\Delete(
     *     path="/stores/{id}",
     *     tags={"Stores"},
     *     summary="Delete a store",
     *     description="Delete a store by its ID for the authenticated seller.",
     *     operationId="deleteStore",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the store to delete",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Store deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store deleted successfully"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", ref="#/components/schemas/Store")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Store not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Store not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", ref="#/components/responses/401"),
     *     @OA\Response(response=403, description="Forbidden", ref="#/components/responses/403"),
     * )
     */

    public function destroy(string $id)
    {
        $store = Store::find($id);
        if (!$store) {
            return ResponseHelper::error([], 'Store not found.', 404);
        }

        $store->delete();

        return ResponseHelper::success(
            [],
            'Store deleted successful.',
            204
        );
    }



    /**
     * @OA\Get(
     *     path="/stores/{id}/online-status",
     *     summary="Get store online status",
     *     description="Returns a boolean flag indicating whether the specified store is currently online.",
     *     tags={"Stores"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the store to check",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Online status retrieved",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Online status"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="is_online", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Store not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="store not found."),
     *             @OA\Property(property="code", type="integer", example=404),
     *             
     *         )
     *     )
     * )
     */

    public function status(string $id)
    {

        $store = Store::find($id);

        if (!$store) {
            return ResponseHelper::error([], "store not found.", 404);
        }


        $isOnline = $store->is_online == 1;
        return ResponseHelper::success(['is_online' => $isOnline], "Online status");
    }





    /**
     * @OA\Patch(
     *     path="/stores/{id}/online-status",
     *     summary="Toggle store online status",
     *     description="Toggles the store's online/offline status. Only the authenticated seller who owns the store may perform this action.",
     *     tags={"Stores"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the store whose status will be toggled",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Store status changed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="store status changed successfully."),
     *             @OA\Property(property="code", type="integer", example=200),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="User is not authorized to change this store",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You are not authorized to change this store."),
     *             @OA\Property(property="code", type="integer", example=403),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Store not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="store not found."),
     *             @OA\Property(property="code", type="integer", example=404),
     *             
     *         )
     *     )
     * )
     */

    public function updateStatus(Request $request, string $id)
    {
        $store = Store::find($id);

        if (!$store) {
            return ResponseHelper::error([], "store not found.", 404);
        }


        if ($store->seller_id !== auth()->id()) {
            return ResponseHelper::error([], "You are not authorized to change this store.", 403);
        }

        $store->is_online = !$store->is_online;
        $store->save();

        return ResponseHelper::success([], "store status changed successfully.");
    }




    /**
     * @OA\Get(
     *     path="/stores/{id}/detailed",
     *     summary="Get detailed information about a store",
     *     description="Returns full store details (followers, owner user, products, region, category, and reviews) for a store that is currently online. 
     *                  Records a visit with optional authenticated user data if a bearer token is provided.",
     *     tags={"Stores"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the store to retrieve",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Store details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store details"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Full store object with related models",
     *                 @OA\Property(property="id", type="integer", example=42),
     *                 @OA\Property(property="name", type="string", example="Downtown Electronics"),
     *                 @OA\Property(property="is_online", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="followers",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="buyer_id", type="integer", example=17)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="name", type="string", example="Jane Seller")
     *                 ),
     *                 @OA\Property(
     *                     property="products",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=101),
     *                         @OA\Property(property="title", type="string", example="4K TV")
     *                     )
     *                 ),
     *                 @OA\Property(property="region", type="object"),
     *                 @OA\Property(property="category", type="object"),
     *                 @OA\Property(property="reviews", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Store not found or not online",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Store not found."),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     )
     * )
     */

    public function detailed(Request $request, string $id)
    {

        $store = Store::with([
            'followers',
            'user',
            'products',
            'region',
            'category',
            'reviews'
        ])->where('is_online', true)->find($id);

        if (!$store) {
            return ResponseHelper::error([], 'Store not found.', 404);
        }


        $token = $request->bearerToken();
        $user = null;

        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $user = $accessToken->tokenable;
            }
        }

        StoreVisit::create([
            'store_id' => $store->id,
            'user_id' => $user && $user->role !== 'seller' ? $user->id ?? null : null,
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
        ]);


        return ResponseHelper::success($store, 'Store details');


    }

    /**
     * @OA\Get(
     *     path="/stores/active-store",
     *     summary="Get the seller's active store",
     *     description="Returns details of the currently active store for the authenticated seller.",
     *     tags={"Stores"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Active store details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Active Store details"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="Main Outlet"),
     *                 @OA\Property(property="slug", type="string", example="main-outlet"),
     *                 @OA\Property(property="is_online", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Seller account not set",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Seller account not set."),
     *             @OA\Property(property="code", type="integer", example=400)
     *         )
     *     )
     * )
     **/

    public function active()
    {
        $authId = auth()->user()->id;

        $seller = Seller::with('store')->where('user_id', $authId)->first();
        if (!$seller) {
            return ResponseHelper::error([], 'Seller account not set.', 400);
        }

        $data = [
            'id' => $seller->active_store,
            'name' => $seller->store->name,
            'slug' => $seller->store->slug,
            'is_online' => $seller->store->is_online
        ];

        return ResponseHelper::success(
            $data,
            'Active Store details'
        );

    }


    /**
     *
     * @OA\Patch(
     *     path="/stores/active-store",
     *     summary="Update the seller's active store",
     *     description="Sets a different store as the authenticated seller's active store.",
     *     tags={"Stores"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"store_id"},
     *             @OA\Property(property="store_id", type="integer", example=7)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Active store updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Active Store Changed successfull."),
     *             @OA\Property(property="code", type="integer", example=200),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Seller account not set or store already active",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Can not set same store as active"),
     *             @OA\Property(property="code", type="integer", example=400),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/422"
     *     )
     * )
     */

    public function updateActive(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'store_id' => 'required|numeric|exists:stores,id'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields',
                422
            );
        }

        $authId = auth()->user()->id;
        $seller = Seller::where('user_id', $authId)->first();

        if ($seller->active_store == $request->store_id) {
            return ResponseHelper::error([], "Can not set same store as active", 400);
        }


        $seller->active_store = $request->store_id;

        $seller->save();

        return ResponseHelper::success([], 'Active Store Changed successfull.', 200);

    }


    /**
     * @OA\Get(
     *     path="/stores/all",
     *     summary="List all stores",
     *     description="Returns a list of all stores that are currently available.  
     *                  If a valid bearer token is provided and logged in user is Super admin, returns both online and offline stores. If not then it only returns the online stores.",
     *     tags={"Stores"},
     *     @OA\Response(
     *         response=200,
     *         description="List of all stores",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All Store listings"),
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
     *                     @OA\Property(property="product_count", type="integer", example=123),
     *                     @OA\Property(property="is_online", type="boolean", example=false)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function all(Request $request)
    {
        $token = $request->bearerToken();
        $user = null;

        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $user = $accessToken->tokenable;
            }
        }

        $query = Store::with('category', 'region.country', 'reviews', 'products');


        if ($user && $user->user_type == 'super_admin') {


        } else {
            $query = $query->where('is_online', true);
        }



        $stores = $query->get()->map(function ($store) {
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
                'product_count' => $store->products->count(),
                'is_online' => $store->is_online
            ];
        });

        return ResponseHelper::success($stores, 'List of all stores.');
    }
}
