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



    public function destroy($id)
    {
        $store = Store::find($id);

        if (!$store) {
            return ResponseHelper::error([], 'Store not found', 404);
        }
        $store->delete();

        return ResponseHelper::success([], 'Store deleted successful.');
    }


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
