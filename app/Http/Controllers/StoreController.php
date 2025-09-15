<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Store;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StoreController extends Controller implements HasMiddleware
{


    public static function middleware(): array
    {
        return [
            new Middleware(
                'auth:sanctum',
                only: ['index']
            ),
            new Middleware(['auth:sanctum', 'user.type:seller,super_admin'], only: [
                'store',
                'update',
                'destroy',
                'status'
            ]),
        ];
    }


    /**
     * @OA\Get(
     *     path="/stores",
     *     tags={"Stores"},
     *     summary="List stores for the authenticated seller",
     *     description="Retrieve all stores owned by the authenticated seller.",
     *     operationId="getStores",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Store listings retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store listings"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Store")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */

    public function index()
    {
        $authId = auth()->user()->id;

        $stores = Store::where("seller_id", $authId)->get();

        return ResponseHelper::success($stores, "Store listings");
    }


    /**
     * Store a newly created resource in storage.
     */

    /**
     * @OA\Post(
     *     path="/stores",
     *     tags={"Stores"},
     *     summary="Create a new store",
     *     description="Create a new store for the authenticated seller.",
     *     operationId="createStore",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Store")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Store created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Store")
     *     ),
     *     @OA\Response(response=400, description="Account info not set or DB error"),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'is_online' => 'nullable|boolean',
            'contact_mobile' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email',
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

        try {

            $sellerId = auth()->id();
            $data = $validator->validated();


            $data['seller_id'] = $sellerId;

            $store = Store::create($data);

            return ResponseHelper::success($store, 'Store created successfully', 201);

        } catch (QueryException $e) {
            return ResponseHelper::error(
                [],
                "DB Error : " . $e->getMessage(),
                400
            );
        } catch (Exception $e) {
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */

    /**
     * @OA\Put(
     *     path="/stores/{id}",
     *     tags={"Stores"},
     *     summary="Update a store",
     *     description="Update an existing store by its ID.",
     *     operationId="updateStore",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the store to update",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Store")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Store updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Store")
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
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Internal server error")
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
            'is_online' => 'nullable|boolean',
            'contact_mobile' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email',
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
            $store->update($data);

            return ResponseHelper::success($store, 'Store updated successfully');

        } catch (Exception $e) {
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */

    /**
     * @OA\Get(
     *     path="/stores/{id}",
     *     tags={"Stores"},
     *     summary="Get a single store",
     *     description="Retrieve details of a store by its ID for the authenticated seller.",
     *     operationId="showStore",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the store",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Store details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store retrieved successfully"),
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
     *             
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */

    public function show(string $id)
    {
        $store = Store::find($id);
        if (!$store) {
            return ResponseHelper::error([], 'Store not found.', 404);
        }

        return ResponseHelper::success(
            $store,
            'Store details',
            200
        );
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
     *             
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Internal server error")
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
     *     path="/stores/{id}/list",
     *     tags={"Stores"},
     *     summary="List stores for a specific user",
     *     description="Retrieve all stores owned by a specific user using their ID.",
     *     operationId="listUserStores",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the user",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Stores retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of stores for the user."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Store")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found"),
     *             @OA\Property(
     *               property="code",
     *               type="integer",
     *               example=404
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function list(string $id)
    {
        $stores = Store::where('seller_id', $id)->get();

        return ResponseHelper::success($stores, "List of stores for the user.");
    }



    /**
     * @OA\Get(
     *     path="/stores/all",
     *     tags={"Stores"},
     *     summary="List all stores",
     *     description="Retrieve a list of all stores in the system, regardless of the seller.",
     *     operationId="listAllStores",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="All stores retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of all stores."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Store")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function all()
    {
        $stores = Store::all();

        return ResponseHelper::success($stores, "List of all stores.");
    }



    /**
     * @OA\Patch(
     *     path="/stores/{id}/online",
     *     tags={"Stores"},
     *     summary="Toggle store online status",
     *     description="Switch the online status of a store. Only the owner of the store can perform this action.",
     *     operationId="toggleStoreStatus",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the store to update",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Store status changed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="store status changed successfully."),
     *             
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Store not found"),
     *     @OA\Response(response=403, description="Unauthorized: You do not own this store"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function status(Request $request, string $id)
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
     * @OA\Post(
     *     path="/stores/{id}/follow",
     *     tags={"Stores"},
     *     summary="Follow or unfollow a store",
     *     description="Toggle following status for a store. If the user already follows the store, it will unfollow; otherwise, it will follow.",
     *     operationId="followStore",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the store to follow or unfollow",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Follow/unfollow successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Followed the store"),
     *             
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Store not found"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function followStore(Request $request, string $id)
    {
        $store = Store::find($id);

        if (!$store) {
            return ResponseHelper::error([], "Store not found", 404);
        }

        $buyerId = auth()->id();

        $existingFollow = \DB::table('store_follows')
            ->where('store_id', $store->id)
            ->where('buyer_id', $buyerId)
            ->first();

        if ($existingFollow) {

            \DB::table('store_follows')
                ->where('id', $existingFollow->id)
                ->delete();

            return ResponseHelper::success([], "Unfollowed the store");
        } else {

            \DB::table('store_follows')->insert([
                'store_id' => $store->id,
                'buyer_id' => $buyerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ResponseHelper::success([], "Followed the store");
        }
    }


    /**
     * @OA\Get(
     *     path="/stores/following",
     *     tags={"Stores"},
     *     summary="Get stores the authenticated user is following",
     *     description="Retrieve a list of stores that the logged-in user is currently following.",
     *     operationId="getFollowingStores",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of followed stores retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stores you are following"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Store")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function following()
    {
        $buyerId = auth()->id();

        $stores = Store::whereHas('followers', function ($query) use ($buyerId) {
            $query->where('buyer_id', $buyerId);
        })->get();

        return ResponseHelper::success($stores, "Stores you are following");
    }


}
