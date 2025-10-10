<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Store;
use App\Helpers\ResponseHelper;

class SearchController extends Controller
{


    /**
     * @OA\Get(
     *     path="/search",
     *     tags={"Search"},
     *     summary="Global search for products and stores",
     *     description="Search across products and stores that are marked as online.",
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Search keyword",
     *         @OA\Schema(type="string", example="shoes")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Maximum number of results to return per type (default: 10)",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Search results"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="query", type="string", example="shoes"),
     *                 @OA\Property(
     *                     property="products",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=12),
     *                         @OA\Property(property="name", type="string", example="Running Shoe"),
     *                         @OA\Property(property="store_id", type="integer", example=5),
     *                         @OA\Property(property="price", type="number", format="float", example=49.99)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="stores",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="name", type="string", example="Sports Hub"),
     *                         @OA\Property(property="slug", type="string", example="sports-hub"),
     *                         @OA\Property(property="category_id", type="integer", example=3)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Missing or invalid query parameter",
     *         ref="#/components/responses/422"
     *     )
     * )
     */

    public function index(Request $request)
    {



        $keyword = trim($request->query('query', '')); // ?q=shoes
        if ($keyword === '') {
            return ResponseHelper::error([], 'Search query is required', 422);
        }
        $limit = $request->query('limit', 10);

        $products = Product::where('is_online', true)->with('store')
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            })
            ->limit($limit)
            ->get(['id', 'name', 'store_id', 'price']);

        $stores = Store::where('is_online', true)->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%");
        })
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'category_id']);

        return ResponseHelper::success([
            'query' => $keyword,
            'products' => $products,
            'stores' => $stores
        ], 'Search results');
    }
}


