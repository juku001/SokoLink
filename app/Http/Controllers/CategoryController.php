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
    /**
     * @OA\Get(
     *     path="/categories",
     *     tags={"Categories"},
     *     summary="Get all categories with store counts",
     *     description="Fetches all categories and includes the number of stores associated with each category.",
     *     operationId="getCategories",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of categories with store counts",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Categories with store counts"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Electronics"),
     *                     @OA\Property(property="slug", type="string", example="electronics"),
     *                     @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="icon", type="string", nullable=true, example="icon.png"),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="stores_count", type="integer", example=25)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index()
    {
        $categories = Category::withCount('stores')->get();

        return ResponseHelper::success($categories, "Categories with store counts");
    }


    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/categories",
     *     tags={"Categories"},
     *     summary="Create a new category",
     *     description="Creates a new category with optional parent and icon.",
     *     operationId="storeCategory",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Electronics"),
     *             @OA\Property(property="icon", type="string", nullable=true, example="icon.png"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=1)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Category added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Category added successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="name", type="string", example="Electronics"),
     *                 @OA\Property(property="slug", type="string", example="electronics"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="icon", type="string", nullable=true, example="icon.png"),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to validate fields"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error: SQLSTATE[...something went wrong...]"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
            'icon' => 'nullable|string',
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
                'icon' => $request->icon,
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
    /**
     * @OA\Get(
     *     path="/categories/{id}",
     *     tags={"Categories"},
     *     summary="Get category details",
     *     description="Fetches the details of a specific category by ID.",
     *     operationId="getCategoryById",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the category",
     *         required=true,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Category details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="category details"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="Electronics"),
     *                 @OA\Property(property="slug", type="string", example="electronics"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="icon", type="string", example="icon.png"),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Category not found"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
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
    /**
     * @OA\Put(
     *     path="/categories/{id}",
     *     tags={"Categories"},
     *     summary="Update a category",
     *     description="Update an existing category by its ID.",
     *     operationId="updateCategory",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the category to update",
     *         required=true,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Updated Electronics"),
     *             @OA\Property(property="icon", type="string", nullable=true, example="new-icon.png"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=2),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Category updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="name", type="string", example="Updated Electronics"),
     *                 @OA\Property(property="slug", type="string", example="updated-electronics"),
     *                 @OA\Property(property="icon", type="string", example="new-icon.png"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true, example=2),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Category not found"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
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
            'icon' => 'nullable|string',
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
                'icon' => $request->icon,
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
    /**
     * @OA\Delete(
     *     path="/categories/{id}",
     *     tags={"Categories"},
     *     summary="Delete a category",
     *     description="Delete a category by its ID.",
     *     operationId="deleteCategory",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the category to delete",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Category successfully deleted",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="category successfully delted"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="Electronics"),
     *                 @OA\Property(property="slug", type="string", example="electronics"),
     *                 @OA\Property(property="icon", type="string", example="icon.png"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Category not found"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
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



    /**
     * @OA\Get(
     *     path="/categories/{id}/stores",
     *     tags={"Categories"},
     *     summary="Get stores under a category",
     *     description="Retrieve all stores belonging to a specific category by its ID.",
     *     operationId="getCategoryStores",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the category",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Stores retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=3),
     *                 @OA\Property(property="name", type="string", example="Electronics"),
     *                 @OA\Property(
     *                     property="stores",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=12),
     *                         @OA\Property(property="name", type="string", example="Tech World"),
     *                         @OA\Property(property="category_id", type="integer", example=3),
     *                         @OA\Property(property="address", type="string", example="123 Main Street"),
     *                         @OA\Property(property="is_active", type="boolean", example=true)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Category not found"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
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


    /**
     * @OA\Get(
     *     path="/categories/{id}/children",
     *     tags={"Categories"},
     *     summary="Get child categories",
     *     description="Retrieve all categories that have the given category as their parent.",
     *     operationId="getCategoryChildren",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the parent category",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Child categories retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Categories by parent ID"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="Smartphones"),
     *                     @OA\Property(property="slug", type="string", example="smartphones"),
     *                     @OA\Property(property="parent_id", type="integer", example=1),
     *                     @OA\Property(property="icon", type="string", example="phone-icon.png"),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Parent category not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Category not found"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function children(Request $request, string $id)
    {
        $categories = Category::where('parent_id', $id)->get();

        return ResponseHelper::success($categories, 'Categories by parent ID', 200);
    }

}
