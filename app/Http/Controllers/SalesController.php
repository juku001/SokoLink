<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\InventoryLedger;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Seller;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Facades\Validator;

class SalesController extends Controller
{
    /**
     * @OA\Get(
     *     path="/dashboard/sales/stats",
     *     tags={"Seller Dashboard"},
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
     *         ref="#/components/responses/401"
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
     *     summary="Create a new sale (single or multiple products)",
     *     description="Creates a sale record with one or more products on the current active store. 
     *                  Deducts stock and records inventory ledger entries if status is `completed`.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"store_id","sales_date","sales_time","status","products"},
     *             @OA\Property(property="store_id", type="integer", example=1, description="Store ID where the sale occurs"),
     *             @OA\Property(property="payment_method_id", type="integer", nullable=true, example=2),
     *             @OA\Property(property="buyer_name", type="string", nullable=true, example="John Doe"),
     *             @OA\Property(property="sales_date", type="string", format="date", example="2025-09-20"),
     *             @OA\Property(property="sales_time", type="string", format="time", example="15:30:00"),
     *             @OA\Property(property="payment_type", type="string", example="cash"),
     *             @OA\Property(property="status", type="string", enum={"pending","completed"}, example="completed"),
     *             @OA\Property(
     *                 property="products",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"product_id","price","quantity"},
     *                     @OA\Property(property="product_id", type="integer", example=5),
     *                     @OA\Property(property="price", type="number", format="float", example=100.50),
     *                     @OA\Property(property="quantity", type="integer", example=2)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Sale recorded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sale recorded successfully"),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="sale_ref", type="string", example="SAL650FAB12"),
     *                 @OA\Property(
     *                     property="sale_products",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="product_id", type="integer", example=5),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="price", type="number", format="float", example=100.50)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/422"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Validation error",
     *         ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */



    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'buyer_name' => 'nullable|string|max:255',
            'payment_type' => 'required|in:cash,mno,bank,card',
            'sales_date' => 'required|date',
            'sales_time' => 'required|date_format:H:i:s',
            'status' => 'required|in:pending,completed',
            // Array of products: each needs id, price, quantity
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.quantity' => 'required|integer|min:1',
        ], [
            'products.*.product_id.exists' => 'Product does not exist',
            'status.in' => 'Status should  be pending or completed',
            'payment_type.in' => 'Payment Type is cash, mno, card or bank'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Validation failed', 422);
        }

        $authId = auth()->id();
        $seller = Seller::where('user_id', $authId)->first();
        $storeId = $seller->active_store;



        DB::beginTransaction();

        try {
            $productsInput = $request->input('products');

            // 1. Lock products to prevent race conditions when deducting stock
            $productIds = collect($productsInput)->pluck('product_id');
            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // 2. Calculate total and validate stock (if completed)
            $totalAmount = 0;
            foreach ($productsInput as $item) {
                $product = $products[$item['product_id']] ?? null;
                if (!$product) {
                    throw new Exception("Product {$item['product_id']} not found.");
                }

                if ($request->status === 'completed' && $item['quantity'] > $product->stock_qty) {
                    throw new Exception("Insufficient stock for {$product->name}.");
                }

                $totalAmount += $item['price'] * $item['quantity'];
            }

            // 3. Create sale record
            $sale = Sale::create([
                'seller_id' => auth()->id(),
                'store_id' => $storeId,
                'payment_method_id' => $request->payment_method_id,
                'payment_type' => $request->payment_type,
                'buyer_name' => $request->buyer_name,
                'amount' => $totalAmount,
                'sales_date' => $request->sales_date,
                'sales_time' => $request->sales_time,
                'status' => $request->status,
            ]);


            foreach ($productsInput as $item) {

                $latestLedger = InventoryLedger::where('store_id', $product->store_id)
                    ->where('product_id', $product->id)
                    ->latest('id')
                    ->first();

                $previousBalance = $latestLedger ? $latestLedger->balance : 0;




                $sale->saleProducts()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);

                if ($request->status === 'completed') {
                    $product = $products[$item['product_id']];
                    $product->decrement('stock_qty', $item['quantity']);
                    $newBalance = $previousBalance - $item['quantity'];


                    InventoryLedger::create([
                        'store_id' => $product->store_id,
                        'product_id' => $product->id,
                        'change' => -1 * $item['quantity'],
                        'balance' => $newBalance,
                        'reason' => 'sale',
                    ]);
                }
            }

            DB::commit();

            return ResponseHelper::success(
                $sale->load('saleProducts.product'),
                'Sale recorded successfully',
                201
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }





    /**
     * @OA\Post(
     *     path="/api/sales/bulk",
     *     summary="Upload an Excel file to create multiple sales records",
     *     description="Accepts an Excel file (.xlsx or .csv) that contains multiple sales with their products.  
     * Each row represents a single sale line item. The API groups rows with the same `sale_ref` into one sale.",
     *     operationId="storeBulkSales",
     *     tags={"Sales"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Excel or CSV file containing the sales data"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bulk sales stored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Bulk sales stored successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Sale")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */


    public function storeBulk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Validation failed', 422);
        }

        $authId = auth()->id();
        $seller = Seller::where('user_id', $authId)->firstOrFail();
        $storeId = $seller->active_store;

        try {
            // Load spreadsheet
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();

            // Read rows (indexed arrays)
            $rows = $sheet->toArray(null, true, true, false);

            /**
             * Expected columns:
             * product_id | quantity | price | payment_method_id | payment_type
             * buyer_name | sales_date | sales_time | status
             */

            // Remove header row if first column is not numeric
            if (isset($rows[0]) && !is_numeric($rows[0][0])) {
                array_shift($rows);
            }

            DB::beginTransaction();

            foreach ($rows as $index => $row) {

                // Map row safely
                [
                    $productId,
                    $qty,
                    $price,
                    $paymentMethod,
                    $paymentType,
                    $buyerName,
                    $salesDate,
                    $salesTime,
                    $status
                ] = array_pad($row, 9, null);


                if (!empty($salesDate)) {

                    if (is_numeric($salesDate)) {
                        // Excel numeric date
                        $salesDate = ExcelDate::excelToDateTimeObject($salesDate)
                            ->format('Y-m-d');

                    } else {
                        $salesDate = Carbon::parse($salesDate)
                            ->format('Y-m-d');
                    }
                }


                if (!empty($salesTime)) {

                    if (is_numeric($salesTime)) {

                        $salesTime = ExcelDate::excelToDateTimeObject($salesTime)
                            ->format('H:i:s');
                    } else {
                        $salesTime = Carbon::parse($salesTime)
                            ->format('H:i:s');
                    }
                }

                // Row-level validation
                $v = Validator::make([
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'price' => $price,
                    'payment_method_id' => $paymentMethod,
                    'payment_type' => $paymentType,
                    'sales_date' => $salesDate,
                    'sales_time' => $salesTime,
                    'status' => $status,
                ], [
                    'product_id' => 'required|exists:products,id',
                    'quantity' => 'required|integer|min:1',
                    'price' => 'required|numeric|min:0',
                    'payment_method_id' => 'nullable|exists:payment_methods,id',
                    'payment_type' => 'required|in:cash,mno,bank,card',
                    'sales_date' => 'required|date',
                    'sales_time' => 'required|date_format:H:i:s',
                    'status' => 'required|in:pending,completed',
                ]);


                if ($v->fails()) {
                    throw new Exception(
                        'Row ' . ($index + 1) . ' validation error: ' .
                        implode(', ', $v->errors()->all())
                    );
                }

                // Lock product for stock consistency
                $product = Product::where('id', $productId)
                    ->lockForUpdate()
                    ->first();

                if ($status === 'completed' && $qty > $product->stock_qty) {
                    throw new Exception(
                        "Row " . ($index + 1) .
                        ": insufficient stock for product {$product->name}"
                    );
                }

                // Create sale
                $sale = Sale::create([
                    'seller_id' => $authId,
                    'store_id' => $storeId,
                    'payment_method_id' => $paymentMethod,
                    'payment_type' => $paymentType,
                    'buyer_name' => $buyerName,
                    'amount' => $price * $qty,
                    'sales_date' => $salesDate,
                    'sales_time' => $salesTime,
                    'status' => $status,
                ]);

                // Attach product to sale
                $sale->saleProducts()->create([
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'price' => $price,
                ]);

                // Stock + inventory ledger
                if ($status === 'completed') {

                    $latestLedger = InventoryLedger::where('store_id', $product->store_id)
                        ->where('product_id', $product->id)
                        ->latest('id')
                        ->first();

                    $previousBalance = $latestLedger
                        ? $latestLedger->balance
                        : $product->stock_qty;

                    $product->decrement('stock_qty', $qty);

                    InventoryLedger::create([
                        'store_id' => $product->store_id,
                        'product_id' => $product->id,
                        'change' => -1 * $qty,
                        'balance' => $previousBalance - $qty,
                        'reason' => 'bulk-sale',
                    ]);
                }
            }

            DB::commit();

            return ResponseHelper::success([], 'Bulk sales imported successfully.', 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }


    // public function storeBulk(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'file' => 'required|file|mimes:xlsx,xls'
    //     ]);

    //     if ($validator->fails()) {
    //         return ResponseHelper::error($validator->errors(), 'Validation failed', 422);
    //     }

    //     $authId = auth()->id();
    //     $seller = Seller::where('user_id', $authId)->firstOrFail();
    //     $storeId = $seller->active_store;

    //     try {
    //         $rows = Excel::toArray([], $request->file('file'))[0]; // first sheet
    //         /**
    //          * Expected columns in Excel:
    //          * product_id | quantity | price | payment_method_id | payment_type | buyer_name | sales_date | sales_time | status
    //          */

    //         // Remove header row if present
    //         if (!is_numeric($rows[0][0])) {
    //             array_shift($rows);
    //         }

    //         DB::beginTransaction();

    //         foreach ($rows as $index => $row) {
    //             // Map row -> fields safely
    //             [$productId, $qty, $price, $paymentMethod, $paymentType, $buyerName, $salesDate, $salesTime, $status] = array_pad($row, 9, null);

    //             // Convert Excel date/time if necessary
    //             if (is_numeric($salesDate)) {
    //                 $salesDate = ExcelDate::excelToDateTimeObject($salesDate)->format('Y-m-d');
    //             }
    //             if (is_numeric($salesTime)) {
    //                 $salesTime = ExcelDate::excelToDateTimeObject($salesTime)->format('H:i:s');
    //             }

    //             // Basic validation per row
    //             $v = Validator::make([
    //                 'product_id' => $productId,
    //                 'quantity' => $qty,
    //                 'price' => $price,
    //                 'payment_method_id' => $paymentMethod,
    //                 'payment_type' => $paymentType,
    //                 'sales_date' => $salesDate,
    //                 'sales_time' => $salesTime,
    //                 'status' => $status,
    //             ], [
    //                 'product_id' => 'required|exists:products,id',
    //                 'quantity' => 'required|integer|min:1',
    //                 'price' => 'required|numeric|min:0',
    //                 'payment_method_id' => 'nullable|exists:payment_methods,id',
    //                 'payment_type' => 'required|in:cash,mno,bank,card',
    //                 'sales_date' => 'required|date',
    //                 'sales_time' => 'required|date_format:H:i:s',
    //                 'status' => 'required|in:pending,completed',
    //             ]);

    //             if ($v->fails()) {
    //                 throw new Exception("Row " . ($index + 1) . " validation error: " . json_encode($v->errors()->all()));
    //             }

    //             // Lock product for stock check
    //             $product = Product::where('id', $productId)->lockForUpdate()->first();

    //             if ($status === 'completed' && $qty > $product->stock_qty) {
    //                 throw new Exception("Row " . ($index + 1) . ": insufficient stock for product {$product->name}");
    //             }

    //             // Create sale
    //             $sale = Sale::create([
    //                 'seller_id' => $authId,
    //                 'store_id' => $storeId,
    //                 'payment_method_id' => $paymentMethod,
    //                 'payment_type' => $paymentType,
    //                 'buyer_name' => $buyerName,
    //                 'amount' => $price * $qty,
    //                 'sales_date' => $salesDate,
    //                 'sales_time' => $salesTime,
    //                 'status' => $status,
    //             ]);

    //             // Link product to sale
    //             $sale->saleProducts()->create([
    //                 'product_id' => $productId,
    //                 'quantity' => $qty,
    //                 'price' => $price,
    //             ]);

    //             // Stock + Ledger
    //             if ($status === 'completed') {
    //                 $latestLedger = InventoryLedger::where('store_id', $product->store_id)
    //                     ->where('product_id', $product->id)
    //                     ->latest('id')->first();

    //                 $previousBalance = $latestLedger ? $latestLedger->balance : $product->stock_qty;
    //                 $product->decrement('stock_qty', $qty);
    //                 $newBalance = $previousBalance - $qty;

    //                 InventoryLedger::create([
    //                     'store_id' => $product->store_id,
    //                     'product_id' => $product->id,
    //                     'change' => -1 * $qty,
    //                     'balance' => $newBalance,
    //                     'reason' => 'bulk-sale',
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         return ResponseHelper::success([], 'Bulk sales imported successfully.', 201);

    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
    //     }
    // }





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
