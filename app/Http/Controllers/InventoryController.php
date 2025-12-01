<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Product;
use App\Models\InventoryLedger;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{




    /**
     * @OA\Get(
     *     path="/product/inventory",
     *     tags={"Inventory"},
     *     summary="List product inventory",
     *     description="Returns a list of products with stock status and latest ledger info.",
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="stock_status",
     *         in="query",
     *         description="Filter by stock status (in_stock, low_stock, out_of_stock)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="image", type="string", nullable=true),
     *                     @OA\Property(property="price", type="number", format="float"),
     *                     @OA\Property(property="category_name", type="string", nullable=true),
     *                     @OA\Property(property="category_slug", type="string", nullable=true),
     *                     @OA\Property(property="store", type="string", nullable=true),
     *                     @OA\Property(property="sold_qty", type="integer"),
     *                     @OA\Property(property="stock_status", type="string"),
     *                     @OA\Property(property="stock_qty", type="integer")
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    public function index(Request $request)
    {
        $products = Product::with([
            'store',
            'category',
            'images' => fn($q) => $q->where('is_cover', true)
        ])
            ->when($request->filled('category_id'), function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            })
            ->when($request->filled('stock_status'), function ($q) use ($request) {

                if ($request->stock_status === 'in_stock') {
                    $q->whereHas('inventoryLedgers', fn($l) => $l->havingRaw('MAX(balance) > 0'));
                }
            })
            ->get()
            ->map(function ($product) {
                $latestLedger = InventoryLedger::where('product_id', $product->id)
                    ->latest('id')
                    ->first();

                $soldQty = InventoryLedger::where('product_id', $product->id)
                    ->where('reason', 'sale')
                    ->sum('change');

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => optional($product->images->first())->path,
                    'price' => $product->price,
                    'category_name' => optional($product->category)->name,
                    'category_slug' => optional($product->category)->slug,
                    'store' => optional($product->store)->name,
                    'sold_qty' => abs($soldQty),                // ensure positive
                    'stock_status' => $product->stock_status,       // accessor
                    'stock_qty' => $latestLedger?->balance ?? 0,
                ];
            });

        return ResponseHelper::success($products, 'Product Inventory List');
    }




    /**
     * Show stock ledger for a specific product
     */



    /**
     * @OA\Get(
     *     path="/products/{id}/inventory",
     *     summary="Show product stock ledger",
     *     description="Returns the full inventory ledger for a specific product, including store and product details.",
     *     tags={"Inventory"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product",
     *         @OA\Schema(type="integer", example=101)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product ledger retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product inventory ledger"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="store_id", type="integer", example=10),
     *                     @OA\Property(property="store_name", type="string", example="City Electronics"),
     *                     @OA\Property(property="product_id", type="integer", example=101),
     *                     @OA\Property(property="product_name", type="string", example="4K TV"),
     *                     @OA\Property(property="change", type="integer", example=10),
     *                     @OA\Property(property="balance", type="integer", example=25),
     *                     @OA\Property(property="reason", type="string", example="restock"),
     *                     @OA\Property(property="created_at", type="string", example="2025-09-19T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $product = Product::with('store')->find($id);

        if (!$product) {
            return ResponseHelper::error([], 'Product not found', 404);
        }

        $ledgerData = InventoryLedger::with(['product', 'store'])
            ->where('product_id', $id)
            ->orderBy('id', 'desc')
            ->get();

        $ledger = $ledgerData->map(function ($ledge) {
            return [
                "id" => $ledge->id,
                "store_id" => $ledge->store_id,
                'store_name' => $ledge->store->name ?? null,
                "product_id" => $ledge->product_id,
                'product_name' => $ledge->product->name ?? null,
                "change" => $ledge->change,
                "balance" => $ledge->balance,
                "reason" => $ledge->reason,
                "created_at" => $ledge->created_at->toIso8601String(),
            ];
        });

        return ResponseHelper::success($ledger, 'Product inventory ledger');
    }


    /**
     * Adjust stock quantity (restock or reduce)
     */


    /**
     * @OA\Patch(
     *     path="/products/{id}/inventory",
     *     summary="Adjust stock quantity for a product",
     *     description="Allows the authenticated seller to adjust the stock quantity for their product.",
     *     tags={"Inventory"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product",
     *         @OA\Schema(type="integer", example=101)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"stock_qty"},
     *             @OA\Property(property="stock_qty", type="integer", example=10),
     *             @OA\Property(property="reason", type="string", example="restock")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stock updated successfully"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="balance", type="integer", example=35)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to adjust this product",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You do not own this product"),
     *             @OA\Property(property="code", type="integer", example=403),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found"),
     *             @OA\Property(property="code", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="code", type="integer", example=422),
     *             @OA\Property(property="data", type="object", example={"stock_qty": {"The stock qty field is required."}})
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product not found', 'code' => 404, 'data' => []], 404);
        }

        // Only store owner can adjust stock
        $authId = auth()->id();
        if ($product->store->seller_id !== $authId) {
            return response()->json(['status' => false, 'message' => 'You do not own this product', 'code' => 403, 'data' => []], 403);
        }

        $validator = Validator::make($request->all(), [
            'stock_qty' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'code' => 422, 'data' => $validator->errors()], 422);
        }

        // Get previous balance
        $latestLedger = InventoryLedger::where('store_id', $product->store_id)
            ->where('product_id', $product->id)
            ->latest('id')
            ->first();

        $previousBalance = $latestLedger ? $latestLedger->balance : 0;
        $newBalance = $previousBalance + $request->stock_qty;

        // Create new ledger entry
        InventoryLedger::create([
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'change' => $request->stock_qty,
            'balance' => $newBalance,
            'reason' => $request->reason ?? 'adjustment'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Stock updated successfully',
            'code' => 200,
            'data' => ['balance' => $newBalance]
        ]);
    }

    /**
     * Get only the current stock balance for a product
     */



    /**
     * @OA\Get(
     *     path="/products/{id}/inventory/balance",
     *     summary="Get current stock balance of a product",
     *     description="Returns only the latest balance for a specific product.",
     *     tags={"Inventory"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product",
     *         @OA\Schema(type="integer", example=101)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product stock balance retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product stock balance"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="balance", type="integer", example=35)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             
     *         )
     *     )
     * )
     */

    public function balance($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product not found', 'code' => 404, 'data' => []], 404);
        }

        $latestLedger = InventoryLedger::where('product_id', $id)
            ->latest('id')
            ->first();

        $balance = $latestLedger ? $latestLedger->balance : 0;

        return response()->json([
            'status' => true,
            'message' => 'Product stock balance',
            'code' => 200,
            'data' => ['balance' => $balance]
        ]);
    }



    /**
     * @OA\Post(
     *     path="/products/{id}/add",
     *     summary="Add stock to a product",
     *     description="Increase the stock quantity of a product. Only the store owner (seller) can perform this action.",
     *     tags={"Inventory"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product to restock",
     *         @OA\Schema(type="integer", example=101)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","quantity"},
     *             @OA\Property(property="product_id", type="integer", example=101),
     *             @OA\Property(property="quantity", type="integer", example=20),
     *             @OA\Property(property="description", type="string", example="New shipment arrived")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock successfully added",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product Stock Added"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/422"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized – not the store owner",
     *         ref="#/components/responses/403"
     *     )
     * )
     */


    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|numeric|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields',
                422
            );
        }

        $product = Product::with('store')->find($request->product_id);

        if (!$product) {
            return ResponseHelper::error([], 'Product not found', 404);
        }

        $authId = auth()->user()->id;
        if ($authId !== $product->store->seller_id) {
            return ResponseHelper::error([], 'Product not on your store.');
        }
        DB::beginTransaction();
        try {

            $quantity = $product->stock_qty;
            $newQty = $quantity + $request->quantity;
            $product->stock_qty = $newQty;
            $product->save();

            $latestLedger = InventoryLedger::where('store_id', $product->store_id)
                ->where('product_id', $product->id)
                ->latest('id')
                ->first();

            $previousBalance = $latestLedger ? $latestLedger->balance : 0;
            $newBalance = $previousBalance + $request->quantity;

            InventoryLedger::create([
                'store_id' => $product->store_id,
                'product_id' => $product->id,
                'change' => $request->quantity,
                'balance' => $newBalance,
                'reason' => 'restock',
                'description' => $request->description
            ]);


            DB::commit();
            return ResponseHelper::success([], 'Product Stock Added');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error : ' . $e->getMessage(), 500);
        }
    }



    /**
     * @OA\Delete(
     *     path="/products/{id}/deduct",
     *     summary="Deduct stock from a product",
     *     description="Decrease the stock quantity of a product (e.g., for expired or damaged items). Only the store owner (seller) can perform this action.",
     *     tags={"Inventory"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product to deduct stock from",
     *         @OA\Schema(type="integer", example=101)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","quantity"},
     *             @OA\Property(property="product_id", type="integer", example=101),
     *             @OA\Property(property="quantity", type="integer", example=5),
     *             @OA\Property(property="description", type="string", example="Damaged items removed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock successfully deducted",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product Stock Deducted"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid deduction (e.g., insufficient stock)",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot deduct more than available stock"),
     *             @OA\Property(property="code", type="integer", example=400),
     *             
     *         )
     *         
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/422"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized – not the store owner",
     *         ref="#/components/responses/403"
     *     )
     * )
     */

    public function deduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|numeric|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields',
                422
            );
        }

        $product = Product::with('store')->find($request->product_id);

        if (!$product) {
            return ResponseHelper::error([], 'Product not found', 404);
        }

        $authId = auth()->user()->id;
        if ($authId !== $product->store->seller_id) {
            return ResponseHelper::error([], 'Product not on your store.', 409);
        }
        DB::beginTransaction();
        try {

            $deductQty = (int) $request->quantity;
            if ($product->stock_qty <= 0) {
                return ResponseHelper::error([], "Product is already out of stock.", 400);
            }

            if ($deductQty > $product->stock_qty) {
                return ResponseHelper::error([], "Cannot deduct more than available stock.", 400);
            }
            $product->stock_qty -= $deductQty;
            $product->save();


            $latestLedger = InventoryLedger::where('store_id', $product->store_id)
                ->where('product_id', $product->id)
                ->latest('id')
                ->first();

            $previousBalance = $latestLedger ? $latestLedger->balance : 0;
            $newBalance = $previousBalance - $deductQty;

            InventoryLedger::create([
                'store_id' => $product->store_id,
                'product_id' => $product->id,
                'change' => $deductQty,
                'balance' => $newBalance,
                'reason' => 'expiry',
                'description' => $request->description
            ]);


            DB::commit();
            return ResponseHelper::success([], 'Product Stock Deducted');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error : ' . $e->getMessage(), 500);
        }
    }
}
