<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Sale;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SalesController extends Controller
{
    /**
     * @OA\Get(
     *     path="/dashboard/sales",
     *     tags={"Dashboard"},
     *     summary="Get sales dashboard for the authenticated seller",
     *     description="Retrieve today's sales, pending sales, and total transactions for the current month",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sales dashboard retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sales dashboard"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="today_sales", type="object",
     *                     @OA\Property(property="amount", type="number", format="float", example=1200.50),
     *                     @OA\Property(property="percent", type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=12.5),
     *                         @OA\Property(property="nature", type="string", enum={"positive","negative","neutral"}, example="positive")
     *                     )
     *                 ),
     *                 @OA\Property(property="pending", type="object",
     *                     @OA\Property(property="amount", type="number", format="float", example=500.00),
     *                     @OA\Property(property="count", type="integer", example=3)
     *                 ),
     *                 @OA\Property(property="total_transaction", type="number", format="float", example=4500.75)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */

    public function dashboard()
    {
        $authId = Auth::id();

        $baseQuery = Sale::where('seller_id', $authId);

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $todaySales = (clone $baseQuery)
            ->whereDate('created_at', $today)
            ->sum('amount');

        $yesterdaySales = (clone $baseQuery)
            ->whereDate('created_at', $yesterday)
            ->sum('amount');

        $percentValue = 0;
        $nature = 'neutral';
        if ($yesterdaySales > 0) {
            $percentValue = (($todaySales - $yesterdaySales) / $yesterdaySales) * 100;
            $nature = $percentValue >= 0 ? 'positive' : 'negative';
        }

        $pendingAmount = (clone $baseQuery)->where('status', 'pending')->sum('amount');
        $pendingCount = (clone $baseQuery)->where('status', 'pending')->count();

        $totalTransaction = (clone $baseQuery)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $data = [
            'today_sales' => [
                'amount' => $todaySales,
                'percent' => [
                    'value' => round(abs($percentValue), 2), // 2 decimal places
                    'nature' => $nature
                ]
            ],
            'pending' => [
                'amount' => $pendingAmount,
                'count' => $pendingCount
            ],
            'total_transaction' => $totalTransaction
        ];

        return ResponseHelper::success($data, 'Sales dashboard');
    }



    /**
     * @OA\Get(
     *     path="/sales",
     *     tags={"Sales"},
     *     summary="List sales for authenticated seller",
     *     description="Retrieve a paginated list of sales for the currently authenticated seller, with optional search and status filters",
     *     operationId="listSales",
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Filter by buyer name or sale ID",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by sale status",
     *         required=false,
     *         @OA\Schema(type="string", example="pending")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of sales retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of sales"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="buyer_name", type="string", example="John Doe"),
     *                         @OA\Property(property="status", type="string", example="completed"),
     *                         @OA\Property(property="total_amount", type="number", example=199.99),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-14T12:00:00Z")
     *                     )
     *                 ),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=100)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */

    public function index(Request $request)
    {
        $auth = auth()->id();

        $query = Sale::where('seller_id', $auth);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('buyer_name', 'LIKE', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $result = $query->latest()->paginate(20);

        return ResponseHelper::success($result, 'List of sales');
    }



    /**
     * @OA\Post(
     *     path="/sales",
     *     tags={"Sales"},
     *     summary="Create a new single-product sale",
     *     description="Records a new sale for a single product",
     *     operationId="createSale",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="quantity", type="number", example=2),
     *             @OA\Property(property="sale_price", type="number", example=199.99),
     *             @OA\Property(property="payment_method_id", type="integer", example=1),
     *             @OA\Property(property="buyer_name", type="string", example="John Doe"),
     *             @OA\Property(property="sale_date", type="string", format="date", example="2025-09-14"),
     *             @OA\Property(property="sale_time", type="string", format="H:i:s", example="14:30:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="New single-product sale added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="New single-product sale added"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="quantity", type="number", example=2),
     *                 @OA\Property(property="price", type="number", example=199.99),
     *                 @OA\Property(property="products", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Product Name"),
     *                         @OA\Property(property="pivot", type="object",
     *                             @OA\Property(property="quantity", type="number", example=2),
     *                             @OA\Property(property="price", type="number", example=199.99)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation or database error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to validate fields"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|numeric|min:1',
            'sale_price' => 'required|numeric|min:0',
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'buyer_name' => 'nullable|string',
            'sale_date' => 'nullable|date',
            'sale_time' => 'nullable|date_format:H:i:s'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields');
        }

        DB::beginTransaction();

        try {
            $saleData = [
                'quantity' => $request->quantity,
                'price' => $request->sale_price,
                'payment_method_id' => $request->payment_method_id,
                'buyer_name' => $request->buyer_name,
                'sale_date' => $request->sale_date,
                'sale_time' => $request->sale_time,
            ];

            $sale = Sale::create($saleData);

            $sale->products()->attach($request->product_id, [
                'quantity' => $request->quantity,
                'price' => $request->sale_price
            ]);

            DB::commit();

            return ResponseHelper::success($sale->load('products'), 'New single-product sale added', 201);

        } catch (QueryException $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error DB: ' . $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error: ' . $e->getMessage());
        }
    }



    /**
     * @OA\Post(
     *     path="/sales/bulk",
     *     tags={"Sales"},
     *     summary="Create a new bulk-product sale",
     *     description="Records a new sale for multiple products at once",
     *     operationId="createBulkSale",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="products",
     *                 type="array",
     *                 description="List of products in the sale",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="quantity", type="number", example=2),
     *                     @OA\Property(property="price", type="number", example=199.99)
     *                 )
     *             ),
     *             @OA\Property(property="payment_method_id", type="integer", example=1),
     *             @OA\Property(property="buyer_name", type="string", example="John Doe"),
     *             @OA\Property(property="sale_date", type="string", format="date", example="2025-09-14"),
     *             @OA\Property(property="sale_time", type="string", format="H:i:s", example="14:30:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="New bulk-product sale added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="New bulk-product sale added"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="products", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Product Name"),
     *                         @OA\Property(property="pivot", type="object",
     *                             @OA\Property(property="quantity", type="number", example=2),
     *                             @OA\Property(property="price", type="number", example=199.99)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation or database error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to validate fields"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */

    public function storeBulk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|integer|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.price' => 'required|numeric|min:0',
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'buyer_name' => 'nullable|string',
            'sale_date' => 'nullable|date',
            'sale_time' => 'nullable|date_format:H:i:s'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields');
        }

        DB::beginTransaction();

        try {
            $saleData = [
                'payment_method_id' => $request->payment_method_id,
                'buyer_name' => $request->buyer_name,
                'sale_date' => $request->sale_date,
                'sale_time' => $request->sale_time,
            ];

            $sale = Sale::create($saleData);

            foreach ($request->products as $product) {
                $sale->products()->attach($product['id'], [
                    'quantity' => $product['quantity'],
                    'price' => $product['price']
                ]);
            }

            DB::commit();

            return ResponseHelper::success($sale->load('products'), 'New bulk-product sale added', 201);

        } catch (QueryException $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error DB: ' . $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error: ' . $e->getMessage());
        }
    }




    /**
     * @OA\Get(
     *     path="/sales/{id}",
     *     tags={"Sales"},
     *     summary="Get sale details",
     *     description="Retrieve detailed information about a specific sale, including the seller, products, and associated store",
     *     operationId="getSale",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the sale",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sale details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sale details"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="buyer_name", type="string", example="John Doe"),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="total_amount", type="number", example=199.99),
     *                 @OA\Property(property="seller", type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Seller Name")
     *                 ),
     *                 @OA\Property(property="store", type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="Store Name")
     *                 ),
     *                 @OA\Property(property="products", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=10),
     *                         @OA\Property(property="name", type="string", example="Product Name"),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="price", type="number", example=99.99)
     *                     )
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-14T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sale not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Sale not found"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */


    public function show(string $id)
    {
        $sale = Sale::with('seller', 'products', 'store')->find($id);
        if (!$sale) {
            return ResponseHelper::error([], 'Sale not found.', 404);
        }

        return ResponseHelper::success($sale, 'Sale details');
    }




    public function update()
    {

    }
}
