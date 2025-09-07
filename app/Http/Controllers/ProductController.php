<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\Request;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Models\Product;
use App\Models\Store;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductsImport;



class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $authId = auth()->id();

        $query = Product::with(['categories', 'images', 'store'])
            ->whereHas('store', function ($q) use ($authId) {
                $q->where('seller_id', $authId);
            });

        // 🔍 Search by name, sku, barcode
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('sku', 'like', "%$search%")
                    ->orWhere('barcode', 'like', "%$search%");
            });
        }

        // 🎯 Filter by category
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // 🛒 Filter by store
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // 💰 Filter by price range
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        // ✅ Filter by online status
        if ($request->filled('is_online')) {
            $query->where('is_online', $request->is_online);
        }

        // Pagination (default 15 per page)
        $products = $query->paginate($request->get('per_page', 15));

        return ResponseHelper::success($products, 'Products retrieved successfully');
    }


    /**
     * Store a newly created resource in storage.

     * Store a new product
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'barcode' => 'nullable|string|max:50|unique:products,barcode',
            'is_online' => 'nullable|boolean',
            'stock_qty' => 'nullable|integer|min:0',

            // categories
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:categories,id',

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

            // create product
            $product = Product::create($data);

            // attach categories
            foreach ($data['categories'] as $categoryId) {
                ProductCategory::create([
                    'product_id' => $product->id,
                    'category_id' => $categoryId,
                ]);
            }

            // attach images
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

            DB::commit();
            return ResponseHelper::success($product->load(['categories', 'images']), 'Product created successfully', 201);

        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }




    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::with(['categories', 'images', 'store'])->find($id);

        if (!$product) {
            return ResponseHelper::error([], 'Product not found', 404);
        }

        // Ensure the authenticated seller owns this product
        if ($product->store->seller_id !== auth()->id()) {
            return ResponseHelper::error([], 'Unauthorized: You cannot view this product.', 403);
        }

        return ResponseHelper::success($product, 'Product details retrieved successfully');
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $product = Product::with(['categories', 'images'])->find($id);

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
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',

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

            // sync categories if provided
            if (isset($data['categories'])) {
                ProductCategory::where('product_id', $product->id)->delete();
                foreach ($data['categories'] as $categoryId) {
                    ProductCategory::create([
                        'product_id' => $product->id,
                        'category_id' => $categoryId,
                    ]);
                }
            }

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
            $product->delete(); // soft delete (since your migration uses softDeletes)
            return ResponseHelper::success([], 'Product deleted successfully');
        } catch (Exception $e) {
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }



    public function all(Request $request)
    {
        $query = Product::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $products = $query->get();

        return ResponseHelper::success($products, 'List of products');
    }


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




    public function stores(Request $request, string $id)
    {
        $store = Store::find($id);

        if (!$store) {
            return ResponseHelper::error([], "Store not found", 404);
        }

        // Start query for products of this store
        $query = Product::where('store_id', $store->id);

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




}
