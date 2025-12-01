<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminUserManagementController extends Controller
{

    /**
     * @OA\Get(
     *     path="/manage/users",
     *     summary="Get list of stores and associated users",
     *     description="Retrieve all stores with related user info. Supports search by store/user name or phone, and filter by user status",
     *     tags={"Admin Manage Users"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for store name, user name, or phone",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by user status (active, suspended, inactive)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stores retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stores retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="store_id", type="integer", example=1),
     *                     @OA\Property(property="store_name", type="string", example="My Store"),
     *                     @OA\Property(property="store_slug", type="string", example="my-store"),
     *                     @OA\Property(property="joined_at", type="string", format="date-time", example="2025-09-16T12:00:00"),
     *                     @OA\Property(property="user_id", type="integer", example=10),
     *                     @OA\Property(property="user_name", type="string", example="John Doe"),
     *                     @OA\Property(property="user_mobile", type="string", example="+255123456789"),
     *                     @OA\Property(property="user_status", type="string", example="active")
     *                 )
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function index(Request $request)
    {
        $query = Store::with('user');

        if ($request->has('status') && $request->status !== null) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $stores = $query->orderBy('created_at', 'desc')->get();

        $data = $stores->map(function ($store) {
            return [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'store_slug' => $store->slug,
                'joined_at' => $store->created_at,
                'user_id' => $store->user->id,
                'user_name' => $store->user->name,
                'user_mobile' => $store->user->phone,
                'user_status' => $store->user->status,
            ];
        });

        return ResponseHelper::success($data, "Stores retrieved successfully");
    }




    /**
     * @OA\Post(
     *     path="/manage/users",
     *     summary="Create a new store and user",
     *     description="Creates a new user (seller) and associated store",
     *     tags={"Admin Manage Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="phone", type="string", example="+255712345678"),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="store_name", type="string", example="John's Store"),
     *             @OA\Property(property="store_email", type="string", example="store@example.com"),
     *             @OA\Property(property="store_phone", type="string", example="+255712345678"),
     *             @OA\Property(property="description", type="string", example="Store description"),
     *             @OA\Property(property="address", type="string", example="123 Main Street"),
     *             @OA\Property(property="region_id", type="integer", example=1),
     *             @OA\Property(property="category_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Store added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store added successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="store", type="object"),
     *                 @OA\Property(property="user", type="object")
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^\+255\d{9}$/|unique:users,phone',
            'name' => 'required|string',
            'email' => 'nullable|string|email|unique:users,email',
            'store_name' => 'required|string',
            'store_email' => 'nullable|string',
            'store_phone' => 'nullable|string',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'region_id' => 'nullable|numeric|exists:regions,id',
            'category_id' => 'nullable|numeric|exists:categories,id'
        ], [
            'phone.regex' => 'Phone number should be +255XXXXXXXXX'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields.', 422);
        }

        DB::beginTransaction();
        try {

            $userData = [
                'phone' => $request->phone,
                'name' => $request->name,
                'email' => $request->email,
                'role' => 'seller',
                'created_by' => auth()->user()->id
            ];
            $user = User::create($userData);

            $storeData = [
                'seller_id' => $user->id,
                'name' => $request->store_name,
                'email' => $request->store_email,
                'phone' => $request->store_phone,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'address' => $request->address,
                'region_id' => $request->region_id
            ];

            $store = Store::create($storeData);

            DB::commit();

            return ResponseHelper::success([
                'store' => $store,
                'user' => $user
            ], 'Store added successful', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error(
                [],
                $e->getMessage(),
            );
        }
    }





    /**
     * @OA\Get(
     *     path="/manage/users/{id}",
     *     summary="Get store details",
     *     description="Retrieve a specific store and associated user info by store ID",
     *     tags={"Admin Manage Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="Store ID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Store details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store details"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Store not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Store not found"),
     *             
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function show($id)
    {



        $store = Store::with(
            [
                'user',
                'category',
                'products',
                'reviews',
                'followers'
            ]
        )->find($id);

        if (!$store) {
            return ResponseHelper::error([], 'Store not found', 404);
        }


        return ResponseHelper::success($store, 'Store details');
    }




    /**
     * @OA\Put(
     *     path="/manage/users/{id}",
     *     summary="Update store and user",
     *     description="Update an existing store and its user",
     *     tags={"Admin Manage Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="Store ID"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="phone", type="string", example="+255712345678"),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="store_name", type="string", example="John's Store"),
     *             @OA\Property(property="store_email", type="string", example="store@example.com"),
     *             @OA\Property(property="store_phone", type="string", example="+255712345678"),
     *             @OA\Property(property="description", type="string", example="Store description"),
     *             @OA\Property(property="address", type="string", example="123 Main Street"),
     *             @OA\Property(property="region_id", type="integer", example=1),
     *             @OA\Property(property="category_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Store updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="store", type="object"),
     *                 @OA\Property(property="user", type="object")
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^\+255\d{9}$/|unique:users,phone,' . $id,
            'name' => 'required|string',
            'email' => 'nullable|string|email|unique:users,email,' . $id,
            'store_name' => 'required|string',
            'store_email' => 'nullable|string|email',
            'store_phone' => 'nullable|string',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'region_id' => 'nullable|numeric|exists:regions,id',
            'category_id' => 'nullable|numeric|exists:categories,id'
        ], [
            'phone.regex' => 'Phone number should be +255XXXXXXXXX'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields.', 422);
        }

        DB::beginTransaction();
        try {
            // Find store and related user
            $store = Store::findOrFail($id);
            $user = $store->user; // assuming Store belongsTo User

            // Update user
            $user->update([
                'phone' => $request->phone,
                'name' => $request->name,
                'email' => $request->email,
            ]);

            // Update store
            $store->update([
                'name' => $request->store_name,
                'email' => $request->store_email,
                'phone' => $request->store_phone,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'address' => $request->address,
                'region_id' => $request->region_id
            ]);

            DB::commit();

            return ResponseHelper::success([
                'store' => $store,
                'user' => $user
            ], 'Store updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], $e->getMessage(), 500);
        }
    }


    /**
     * @OA\Delete(
     *     path="/manage/users/{id}",
     *     summary="Delete store",
     *     description="Delete a store by its ID",
     *     tags={"Admin Manage Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="Store ID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Store deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store deleted successful."),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Store not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Store not found"),
     *             
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function destroy($id)
    {
        $store = Store::find($id);

        if (!$store) {
            return ResponseHelper::error([], 'Store not found', 404);
        }
        $store->delete();

        return ResponseHelper::success([], 'Store deleted successful.');
    }







    /**
     * @OA\Put(
     *     path="/manage/users/{id}/status",
     *     summary="Update user status",
     *     description="Change status of a user (active, suspended, inactive)",
     *     tags={"Admin Manage Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="User ID"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="active", description="Status to set: active, suspended, inactive")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User status changed to Active"),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found"),
     *             
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */


    public function status(Request $request, $id)
    {
        $user = User::where('role', '!=', 'super_admin')->where(
            'id',
            $id
        )->first();
        if (!$user) {
            return ResponseHelper::error([], "User not found", 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,suspended,inactive'
        ]);


        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields", 422);
        }

        $user->status = $request->status;
        $user->save();

        return ResponseHelper::success(
            [],
            "User status changed to " . ucfirst($request->status)
        );
    }
}
