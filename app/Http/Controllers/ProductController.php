<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\InventoryLedger;
use Exception;
use Illuminate\Http\Request;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Models\Product;
use App\Models\Store;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductsImport;



class ProductController extends Controller implements HasMiddleware
{

    public static function middleware()
    {
        return [
            new Middleware(['auth:sanctum', 'user.type:seller'], only: ['store', 'update', 'destroy']),
        ];

    }
    /**
     * Display a listing of the resource.
     */


    /**
     * @OA\Get(
     *     path="/products",
     *     tags={"Products"},
     *     summary="List products for authenticated seller",
     *     description="Retrieve a paginated list of products belonging to the authenticated seller. Supports filtering by search, category, store, price range, online status, and stock status.",
     *     operationId="listProducts",
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by product name, SKU, or barcode",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="store_id",
     *         in="query",
     *         description="Filter by store ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="price_min",
     *         in="query",
     *         description="Filter by minimum price",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="price_max",
     *         in="query",
     *         description="Filter by maximum price",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="is_online",
     *         in="query",
     *         description="Filter by online status (1 = online, 0 = offline)",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="stock_status",
     *         in="query",
     *         description="Filter by stock status (in_stock, low_stock, out_of_stock)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"in_stock", "low_stock", "out_of_stock"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of products per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of products",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Product")),
     *                 @OA\Property(property="first_page_url", type="string"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="last_page_url", type="string"),
     *                 @OA\Property(property="next_page_url", type="string"),
     *                 @OA\Property(property="prev_page_url", type="string"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", ref="#/components/responses/401"),
     *     @OA\Response(response=500, description="Internal server error", ref="#/components/responses/500"),
     * )
     */

    public function index(Request $request)
    {
        $query = Product::with(['category', 'images', 'store']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('sku', 'like', "%$search%")
                    ->orWhere('barcode', 'like', "%$search%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        if ($request->filled('is_online')) {
            $query->where('is_online', $request->is_online);
        }

        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'in_stock':
                    $query->whereColumn('stock_qty', '>', 'low_stock_threshold');
                    break;

                case 'low_stock':
                    $query->whereColumn('stock_qty', '<=', 'low_stock_threshold')
                        ->where('stock_qty', '>', 0);
                    break;

                case 'out_of_stock':
                    $query->where('stock_qty', '=', 0);
                    break;
            }
        }

        $products = $query->paginate($request->get('per_page', 15));

        return ResponseHelper::success($products, 'Products retrieved successfully');
    }



    /**
     * Store a newly created resource in storage.

     * Store a new product
     */
    /**
     * @OA\Post(
     *     path="/products",
     *     tags={"Products"},
     *     summary="Create a new product",
     *     description="Create a new product in a store owned by the authenticated seller.",
     *     operationId="createProduct",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"store_id","name","description","price"},
     *             @OA\Property(property="store_id", type="integer", example=5),
     *             @OA\Property(property="name", type="string", example="Laptop X"),
     *             @OA\Property(property="description", type="string", example="High-end laptop"),
     *             @OA\Property(property="price", type="number", format="float", example=299.99),
     *             @OA\Property(property="sku", type="string", nullable=true, example="LAP12345"),
     *             @OA\Property(property="barcode", type="string", nullable=true, example="1234567890123"),
     *             @OA\Property(property="is_online", type="boolean", example=true),
     *             @OA\Property(property="stock_qty", type="integer", nullable=true, example=10),
     *             @OA\Property(property="stock_status", type="string",example="in_stock"),
     *             @OA\Property(
     *                 property="category_id",
     *                 type="integer",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="path", type="string", example="/images/laptop.png"),
     *                     @OA\Property(property="is_cover", type="boolean", example=true),
     *                     @OA\Property(property="position", type="integer", example=0)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *
     *     @OA\Response(response=403, description="Unauthorized: store not owned by seller",ref="#/components/responses/403"),
     *     @OA\Response(response=422, description="Validation failed",ref="#/components/responses/422"),
     *     @OA\Response(response=401, description="Unauthenticated",ref="#/components/responses/401"),
     *     @OA\Response(response=500, description="Internal server error",ref="#/components/responses/500"),
     * )
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'barcode' => 'nullable|string|max:50|unique:products,barcode',
            'is_online' => 'nullable|boolean',
            'stock_qty' => 'required|integer|min:0',

            // categories
            'category_id' => 'required|numeric|exists:categories,id',
            // 'categories' => 'required|array|min:1',
            // 'categories.*' => 'exists:categories,id',

            // images
            'images' => 'nullable|array',
            'images.*.path' => 'required|string',
            'images.*.is_cover' => 'boolean',
            'images.*.position' => 'integer',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields.', 422);
        }

        DB::beginTransaction();
        try {
            $data = $validator->validated();

            // Ensure seller owns the store
            $store = Store::where('id', $data['store_id'])
                ->where('seller_id', auth()->id())
                ->first();


            if (!$store) {
                return ResponseHelper::error([], 'Unauthorized: Store not found or not yours.', 403);
            }

            $data['slug'] = Str::slug($data['name']);

            $product = Product::create($data);
            // if (isset($data['categories'])) {
            //     foreach ($data['categories'] as $categoryId) {
            //         ProductCategory::create([
            //             'product_id' => $product->id,
            //             'category_id' => $categoryId,
            //         ]);
            //     }
            // }

            if (!empty($data['images'])) {
                foreach ($data['images'] as $image) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $image['path'],
                        'is_cover' => $image['is_cover'] ?? false,
                        'position' => $image['position'] ?? 0,
                    ]);
                }
            }

            $latestLedger = InventoryLedger::where('store_id', $request->store_id)
                ->where('product_id', $product->id)
                ->latest('id')
                ->first();

            $previousBalance = $latestLedger ? $latestLedger->balance : 0;

            $newBalance = $previousBalance + $request->stock_qty;

            InventoryLedger::create([
                'store_id' => $request->store_id,
                'product_id' => $product->id,
                'change' => $request->stock_qty,
                'balance' => $newBalance,
                'reason' => 'restock',
            ]);


            DB::commit();
            return ResponseHelper::success($product->load(['category', 'images']), 'Product created successfully', 201);

        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }




    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/products/{id}",
     *     tags={"Products"},
     *     summary="Get a single product",
     *     description="Retrieve details of a product by its ID. Only the authenticated seller who owns the store can view it.",
     *     operationId="showProduct",

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
     *         description="Product retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized: Seller does not own this product",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized: You cannot view this product."),
     *             @OA\Property(property="code", type="integer", example=403),
     *             
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     * 
     * 
     *             
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated",ref="#/components/responses/401"),
     *     @OA\Response(response=500, description="Internal server error",ref="#/components/responses/500"),
     * )
     */
    public function show($id)
    {
        $product = Product::with(['category', 'images', 'store'])->find($id);

        if (!$product) {
            return ResponseHelper::error([], 'Product not found', 404);
        }

        // // Ensure the authenticated seller owns this product
        // if ($product->store->seller_id !== auth()->id()) {
        //     return ResponseHelper::error([], 'Unauthorized: You cannot view this product.', 403);
        // }

        return ResponseHelper::success($product, 'Product details retrieved successfully');
    }



    /**
     * Update the specified resource in storage.
     */

    /**
     * @OA\Put(
     *     path="/products/{id}",
     *     tags={"Products"},
     *     summary="Update a product",
     *     description="Update a product by ID. Only the seller who owns the store can update the product. Categories and images can also be updated.",
     *     operationId="updateProduct",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product to update",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Laptop X Pro"),
     *             @OA\Property(property="description", type="string", example="Updated high-end laptop"),
     *             @OA\Property(property="price", type="number", format="float", example=349.99),
     *             @OA\Property(property="sku", type="string", example="LAP12345"),
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="barcode", type="string", example="1234567890123"),
     *             @OA\Property(property="is_online", type="boolean", example=true),
     *             @OA\Property(property="stock_qty", type="integer", example=15),
     *             @OA\Property(property="stock_status", type="string",example="in_stock"),
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="path", type="string", example="/images/laptop.png"),
     *                     @OA\Property(property="is_cover", type="boolean", example=true),
     *                     @OA\Property(property="position", type="integer", example=0)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *
     *     @OA\Response(response=403, description="Unauthorized: Seller does not own this product",ref="#/components/responses/403"),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found"),
     *             @OA\Property(property="code", type="integer", example=404), 
     *             
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed",ref="#/components/responses/422"),
     *     @OA\Response(response=401, description="Unauthenticated",ref="#/components/responses/401"),
     *     @OA\Response(response=500, description="Internal server error",ref="#/components/responses/500"),
     * )
     */

    public function update(Request $request, $id)
    {
        $product = Product::with(['category', 'images'])->find($id);

        if (!$product) {
            return ResponseHelper::error([], 'Product not found', 404);
        }

        // Ensure seller owns this product's store
        if ($product->store->seller_id !== auth()->id()) {
            return ResponseHelper::error([], 'Unauthorized: You cannot update this product.', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'sku' => 'nullable|string|max:50|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|max:50|unique:products,barcode,' . $product->id,
            'is_online' => 'nullable|boolean',
            'stock_qty' => 'nullable|integer|min:0',

            // categories
            'category_id' => 'sometimes|required|numeric|exists:categories,id',
            // 'categories' => 'nullable|array',
            // 'categories.*' => 'exists:categories,id',

            // images
            'images' => 'nullable|array',
            'images.*.path' => 'required|string',
            'images.*.is_cover' => 'boolean',
            'images.*.position' => 'integer',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields.', 422);
        }

        DB::beginTransaction();
        try {
            $data = $validator->validated();

            // update product fields
            $updateData = $data;
            if ($request->has('name')) {
                $updateData['slug'] = Str::slug($request->name);
            }
            $product->update($updateData);

            // // sync categories if provided
            // if (isset($data['categories'])) {
            //     ProductCategory::where('product_id', $product->id)->delete();
            //     foreach ($data['categories'] as $categoryId) {
            //         ProductCategory::create([
            //             'product_id' => $product->id,
            //             'category_id' => $categoryId,
            //         ]);
            //     }
            // }

            // sync images if provided
            if (isset($data['images'])) {
                ProductImage::where('product_id', $product->id)->delete();
                foreach ($data['images'] as $image) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $image['path'],
                        'is_cover' => $image['is_cover'] ?? false,
                        'position' => $image['position'] ?? 0,
                    ]);
                }
            }

            DB::commit();
            return ResponseHelper::success($product->fresh()->load(['categories', 'images']), 'Product updated successfully');

        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     *     path="/products/{id}",
     *     tags={"Products"},
     *     summary="Delete a product",
     *     description="Delete a product by ID. Only the seller who owns the store can delete the product. Soft delete is applied.",
     *     operationId="deleteProduct",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product to delete",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product deleted successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Unauthorized: Seller does not own this product", ref="#/components/responses/403"),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found"),
     *             @OA\Property(property="code", type="integer", example=404),

     * 
     *             
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated",ref="#/components/responses/401"),
     *     @OA\Response(response=500, description="Internal server error",ref="#/components/responses/500"),
     * )
     */
    public function destroy($id)
    {
        $product = Product::with('store')->find($id);

        if (!$product) {
            return ResponseHelper::error([], 'Product not found', 404);
        }

        // Ensure the authenticated seller owns this product
        if ($product->store->seller_id !== auth()->id()) {
            return ResponseHelper::error([], 'Unauthorized: You cannot delete this product.', 403);
        }

        try {
            $product->delete(); // soft delete
            return ResponseHelper::success([], 'Product deleted successfully');
        } catch (Exception $e) {
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/products/all",
     *     tags={"Products"},
     *     summary="List all products",
     *     description="Retrieve all products. Optionally filter by name using the `name` query parameter.",
     *     operationId="listAllProducts",
     *
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         required=false,
     *         description="Filter products by name (partial match)",
     *         @OA\Schema(type="string", example="Laptop")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Products retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of products"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Product")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated",ref="#/components/responses/401"),
     *     @OA\Response(response=500, description="Internal server error",ref="#/components/responses/500"),
     * )
     */
    public function all(Request $request)
    {
        $query = Product::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $products = $query->get();

        return ResponseHelper::success($products, 'List of products');
    }





    /**
     * @OA\Patch(
     *     path="/products/{id}/online",
     *     tags={"Products"},
     *     summary="Toggle product online status",
     *     description="Toggle the `is_online` status of a product. Only the authenticated seller who owns the product can perform this action.",
     *     operationId="toggleProductStatus",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product to toggle status",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product status changed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product status changed successfully."),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Unauthorized: Seller does not own this product",ref="#/components/responses/403"),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", ref="#/components/responses/401"),
     *     @OA\Response(response=500, description="Internal server error", ref="#/components/responses/500"),
     * )
     */
    public function status(Request $request, string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return ResponseHelper::error([], "Product not found.", 404);
        }

        // Ensure the authenticated user is the owner of the product
        if ($product->store->seller_id !== auth()->id()) {
            return ResponseHelper::error([], "You are not authorized to change this product.", 403);
        }

        $product->is_online = !$product->is_online;
        $product->save();

        return ResponseHelper::success([], "Product status changed successfully.");
    }




    /**
     * @OA\Post(
     *     path="/products/excel",
     *     tags={"Products"},
     *     summary="Bulk upload products",
     *     description="Upload multiple products using a CSV or XLSX file. The file must contain valid product data according to the import rules.",
     *     operationId="bulkUploadProducts",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"file"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="CSV or XLSX file containing product data"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Products uploaded successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products uploaded successfully."),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Invalid file or validation failed", ref="#/components/responses/422"),
     *     @OA\Response(response=401, description="Unauthenticated", ref="#/components/responses/401"),
     *     @OA\Response(response=500, description="Internal server error", ref="#/components/responses/500"),
     * )
     */
    public function bulk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,csv'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Invalid file', 422);
        }

        try {
            Excel::import(new ProductsImport, $request->file('file'));

            return ResponseHelper::success([], 'Products uploaded successfully.');
        } catch (Exception $e) {
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }





    /**
     * @OA\Get(
     *     path="/stores/{id}/products",
     *     tags={"Products"},
     *     summary="List products of a store",
     *     description="Retrieve all products for a given store. Optional filters for name and category are available.",
     *     operationId="listStoreProducts",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the store",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         required=false,
     *         description="Filter products by name (partial match)",
     *         @OA\Schema(type="string", example="Laptop")
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         required=false,
     *         description="Filter products by category ID",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Products retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products for store"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Product")
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found"),
     *             
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated",ref="#/components/responses/401"),
     *     @OA\Response(response=500, description="Internal server error",ref="#/components/responses/500"),
     * )
     */
    public function stores(Request $request, string $id)
    {
        $store = Store::find($id);

        if (!$store) {
            return ResponseHelper::error([], "Store not found", 404);
        }

        // Start query for products of this store
        $query = Product::with(['store', 'reviews', 'categories', 'images'])->where('store_id', $store->id)->where('is_online', true);

        // Filter by product name
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter by category
        if ($request->has('category_id')) {
            $categoryId = $request->category_id;
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $products = $query->with(['categories', 'images'])->get();

        return ResponseHelper::success($products, "Products for store");
    }




    /**
     * @OA\Get(
     *     path="/products/{id}/detailed",
     *     summary="Get detailed product information",
     *     description="Returns a product along with its related store, reviews, categories, and images.",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product to retrieve",
     *         @OA\Schema(type="integer", example=101)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detailed product information retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Detailed product information"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=101),
     *                 @OA\Property(property="name", type="string", example="4K TV"),
     *                 @OA\Property(property="description", type="string", example="High-definition television."),
     *                 @OA\Property(property="price", type="number", format="float", example=499.99),
     *                 @OA\Property(property="store", type="object"),
     *                 @OA\Property(property="stock_status", type="string",example="in_stock"),
     *                 @OA\Property(property="reviews", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="categories", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="images", type="array", @OA\Items(type="object"))
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

    public function detailed($id)
    {
        $product = Product::
            with(['store', 'reviews', 'categories', 'images'])->
            find($id);
        if (!$product) {
            return ResponseHelper::error([], 'Product not found', 404);
        }


        return ResponseHelper::success($product, 'Detailed product information');
    }


    /**
     * @OA\Patch(
     *     path="/products/{id}/online-status",
     *     summary="Toggle product online status",
     *     description="Allows the authenticated **seller** who owns the product to toggle its online/offline status.",
     *     tags={"Products"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the product to toggle",
     *         @OA\Schema(type="integer", example=101)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product online status changed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product online status changed."),
     *             @OA\Property(property="code", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="User does not own the product",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You do not own this product"),
     *             @OA\Property(property="code", type="integer", example=403)
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
     *     )
     * )
     */

    public function online(string $id)
    {
        $product = Product::with('store')->find($id);
        if (!$product) {
            return ResponseHelper::error([], 'Product not found', 404);
        }

        $authId = auth()->user()->id;

        if ($product->store->seller_id !== $authId) {
            return ResponseHelper::error([], "You do not own this product");
        }

        $product->is_online = !$product->is_online;
        $product->save();


        return ResponseHelper::success([], "Product online status changed.");

    }


}
