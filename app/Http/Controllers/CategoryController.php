<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Category;
use App\Models\Store;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Response;

class CategoryController extends Controller implements HasMiddleware
{

    public static function middleware(): array
    {
        return [
            new Middleware(['auth:sanctum', 'user.type:super_admin'], only: ['store', 'update', 'destroy']),
        ];
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();
        return ResponseHelper::success($categories, "List of categories");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
            'parent_id' => 'nullable|integer|exists:categories,id'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields',
                422
            );
        }

        try {
            $data = [
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'parent_id' => $request->parent_id,
                'is_active' => true
            ];

            $category = Category::create($data);

            return ResponseHelper::success($category, 'Category added successfully', 201);

        } catch (Exception $e) {
            return ResponseHelper::error(
                [],
                "Error: " . $e->getMessage(),
                500
            );
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return ResponseHelper::error([], "Category not found", 404);
        }

        return ResponseHelper::success($category, "category details", 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return ResponseHelper::error([], 'Category not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'parent_id' => 'nullable|integer|exists:categories,id|not_in:' . $category->id,
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields',
                422
            );
        }

        try {
            $data = [
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'parent_id' => $request->parent_id,
                'is_active' => $request->has('is_active') ? $request->is_active : $category->is_active,
            ];

            $category->update($data);

            return ResponseHelper::success($category, 'Category updated successfully');

        } catch (Exception $e) {
            return ResponseHelper::error(
                [],
                'Error: ' . $e->getMessage(),
                500
            );
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return ResponseHelper::error([], "Category not found", 404);
        }

        $category->delete();
        return ResponseHelper::success($category, "category successfully delted", 200);
    }


    public function stores(Request $request, string $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return ResponseHelper::error([], "Category not found", 404);
        }

        $stores = Store::where('category_id', $category->id)->get();
        return ResponseHelper::success([
            "name" => $category->name,
            "id" => $category->id,
            'stores' => $stores
        ], '', 200);

    }

    public function children(Request $request, string $id)
    {
        $categories = Category::where('parent_id', $id)->get();

        return ResponseHelper::success($categories, 'Categories by parent ID', 200);
    }
}
