<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Store;
use Exception;
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
     * Display a listing of the resource.
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

        try {
            $sellerId = auth()->id();

            $data = $validator->validated();
            $data['seller_id'] = $sellerId;
            $data['slug'] = Str::slug($request->name);

            $store = Store::create($data);

            return ResponseHelper::success($store, 'Store created successfully', 201);

        } catch (Exception $e) {
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Update the specified resource in storage.
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

            if ($request->has('name')) {
                $data['slug'] = Str::slug($request->name);
            }

            $store->update($data);

            return ResponseHelper::success($store, 'Store updated successfully');

        } catch (Exception $e) {
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
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

    public function list(string $id)
    {
        $stores = Store::where('seller_id', $id)->get();

        return ResponseHelper::success($stores, "List of stores for the user.");
    }


    public function all()
    {
        $stores = Store::all();

        return ResponseHelper::success($stores, "List of all stores.");
    }


    public function status(Request $request, string $id)
    {
        $store = Store::find($id);

        if (!$store) {
            return ResponseHelper::error([], "store not found.", 404);
        }

        // Ensure the authenticated user is the owner of the store
        if ($store->seller_id !== auth()->id()) {
            return ResponseHelper::error([], "You are not authorized to change this store.", 403);
        }

        $store->is_online = !$store->is_online;
        $store->save();

        return ResponseHelper::success([], "store status changed successfully.");
    }


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
            // Unfollow
            \DB::table('store_follows')
                ->where('id', $existingFollow->id)
                ->delete();

            return ResponseHelper::success([], "Unfollowed the store");
        } else {
            // Follow
            \DB::table('store_follows')->insert([
                'store_id' => $store->id,
                'buyer_id' => $buyerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ResponseHelper::success([], "Followed the store");
        }
    }



    public function following()
    {
        $buyerId = auth()->id();

        $stores = Store::whereHas('followers', function ($query) use ($buyerId) {
            $query->where('buyer_id', $buyerId);
        })->get();

        return ResponseHelper::success($stores, "Stores you are following");
    }

}
