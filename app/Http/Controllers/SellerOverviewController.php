<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Expense;
use App\Models\InventoryLedger;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Seller;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellerOverviewController extends Controller
{


    /**
     * @OA\Get(
     *     path="/dashboard/overview/stats",
     *     tags={"Seller Dashboard"},
     *     summary="These are statistics on the first overview page on the seller dashboard.",
     *     description="Returns key performance metrics for the authenticated seller (sales, expenses, products, customers) including month-over-month percentage changes.",
     *     operationId="getDashboardOverviewStats",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard summary retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dashboard summary"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="sales",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=12345.67),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=12.5),
     *                         @OA\Property(property="nature", type="string", example="positive"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="expenses",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=2345.67),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=8.3),
     *                         @OA\Property(property="nature", type="string", example="negative"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="products",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=150),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=5.2),
     *                         @OA\Property(property="nature", type="string", example="positive"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="customers",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=80),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=3.1),
     *                         @OA\Property(property="nature", type="string", example="negative"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthenticated",
     *         ref="#/components/responses/403"
     *     )
     * )
     */

    public function index()
    {
        $sellerId = auth()->id();
        $storeIds = Store::where('seller_id', $sellerId)->pluck('id');

        $startOfThisMonth = now()->startOfMonth();
        $endOfThisMonth = now();
        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth = now()->subMonth()->endOfMonth();

        $salesThis = Sale::whereIn('store_id', $storeIds)
            ->whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])
            ->sum('amount');

        $salesLast = Sale::whereIn('store_id', $storeIds)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');


        $expensesThis = Expense::whereIn('store_id', $storeIds)
            ->whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])
            ->sum('amount');

        $expensesLast = Expense::whereIn('store_id', $storeIds)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');

        $productsThis = Product::whereIn('store_id', $storeIds)
            ->whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])
            ->count();

        $productsLast = Product::whereIn('store_id', $storeIds)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        $customersThis = Sale::query()
            ->when(!empty($storeIds), fn($q) => $q->whereIn('store_id', $storeIds))
            ->when(
                $startOfThisMonth && $endOfThisMonth,
                fn($q) =>
                $q->whereBetween('sales_date', [$startOfThisMonth, $endOfThisMonth])
            )
            ->distinct('buyer_name')
            ->count('buyer_name');

        $customersLast = Sale::query()
            ->when(!empty($storeIds), fn($q) => $q->whereIn('store_id', $storeIds))
            ->when(
                $startOfLastMonth && $endOfLastMonth,
                fn($q) =>
                $q->whereBetween('sales_date', [$startOfLastMonth, $endOfLastMonth])
            )
            ->distinct('buyer_name')
            ->count('buyer_name');



        $percentChange = function ($last, $current) {
            if ($last == 0) {
                return [
                    'value' => $current > 0 ? 100 : 0,
                    'nature' => $current > 0 ? 'positive' : 'neutral'
                ];
            }
            $change = (($current - $last) / $last) * 100;
            return [
                'value' => round(abs($change), 2),
                'nature' => $change >= 0 ? 'positive' : 'negative'
            ];
        };

        $data = [
            'sales' => [
                'value' => $salesThis,
                'percent' => $percentChange($salesLast, $salesThis) + ['duration' => 'month']
            ],
            'expenses' => [
                'value' => $expensesThis,
                'percent' => $percentChange($expensesLast, $expensesThis) + ['duration' => 'month']
            ],
            'products' => [
                'value' => $productsThis,
                'percent' => $percentChange($productsLast, $productsThis) + ['duration' => 'month']
            ],
            'customers' => [
                'value' => $customersThis,
                'percent' => $percentChange($customersLast, $customersThis) + ['duration' => 'month']
            ],
        ];

        return ResponseHelper::success($data, 'Dashboard summary');
    }




    /**
     * @OA\Get(
     *     path="/reports/seller/top-categories",
     *     tags={"Reports"},
     *     summary="Get top categories by sales contribution",
     *     description="Fetches categories with their total sales amount and percentage contribution based on sales data.",
     *     @OA\Response(
     *         response=200,
     *         description="Top categories retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Top categories by sales contribution"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Electronics"),
     *                     @OA\Property(property="total_amount", type="number", format="float", example=1520000.50),
     *                     @OA\Property(property="percentage", type="number", format="float", example=37.5)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error: something went wrong"),
     *             @OA\Property(property="code", type="integer", example=500)
     *         )
     *     )
     * )
     */

    public function topCategories()
    {
        $categories = \DB::table('sales_products')
            ->join('products', 'sales_products.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'categories.id',
                'categories.name',
                \DB::raw('SUM(sales_products.quantity * sales_products.price) as total_amount')
            )
            ->whereNotNull('products.category_id')
            ->groupBy('categories.id', 'categories.name')
            ->get();

        $grandTotal = $categories->sum('total_amount');

        $categories = $categories->map(function ($cat) use ($grandTotal) {
            $cat->percentage = $grandTotal > 0
                ? round(($cat->total_amount / $grandTotal) * 100, 2)
                : 0;
            return $cat;
        });

        return ResponseHelper::success($categories, 'Top categories by sales contribution');
    }





    /**
     * @OA\Get(
     *     path="/dashboard/overview/recent-sales",
     *     tags={"Seller Dashboard"},
     *     summary="Get recent sales",
     *     description="Fetches the 5 most recent sales of the authenticated seller, including products in each sale.",
     *     @OA\Response(
     *         response=200,
     *         description="Recent sales retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of 5 recent sales"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=12),
     *                     @OA\Property(property="buyer_name", type="string", example="John Doe"),
     *                     @OA\Property(property="amount", type="number", format="float", example=250000.00),
     *                     @OA\Property(property="status", type="string", example="completed"),
     *                     @OA\Property(
     *                         property="products",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=45),
     *                             @OA\Property(property="name", type="string", example="Wireless Mouse"),
     *                             @OA\Property(property="price", type="number", format="float", example=50000.00),
     *                             @OA\Property(property="quantity", type="integer", example=2)
     *                         )
     *                     )
     *                 )
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

    public function recentSales()
    {
        $auth = auth()->user()->id;
        $sales = Sale::with('products')
            ->where('seller_id', $auth)
            ->latest()
            ->limit(5)
            ->get();
        $salesData = [];


        $salesData = $sales->map(function ($sale) use ($auth) {
            return [
                'id' => $sale->id,
                'name' => $sale->buyer_name,
                'amount' => $sale->amount,
                'status' => $sale->status,
                'products' => $sale->products
            ];
        });

        return ResponseHelper::success($salesData, 'List of 5 recent sales');
    }




    /**
     * @OA\Get(
     *     path="/dashboard/overview/low-stock",
     *     summary="Get low stock products",
     *     description="Retrieve a list of products that are below their low stock threshold for the authenticated seller's active store.",
     *     tags={"Seller Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Low stock products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Low stock products retrieved"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="product_id", type="integer", example=15),
     *                     @OA\Property(property="name", type="string", example="Wireless Mouse"),
     *                     @OA\Property(property="sku", type="string", example="WM-001"),
     *                     @OA\Property(property="stock_balance", type="integer", example=3),
     *                     @OA\Property(property="threshold", type="integer", example=10)
     *                 )
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


    public function lowStock()
    {
        $authId = auth()->id();

        // Get seller for the authenticated user
        $seller = Seller::where('user_id', $authId)->firstOrFail();

        // Get the active store of this seller
        $activeStoreId = $seller->active_store;

        // Fetch products with their latest balance from inventory ledgers
        $lowStockProducts = InventoryLedger::query()
            ->select('product_id', DB::raw('MAX(id) as latest_ledger_id'))
            ->where('store_id', $activeStoreId)
            ->groupBy('product_id')
            ->with([
                'latestLedger' => function ($query) {
                    $query->select('id', 'product_id', 'balance');
                },
                'product' => function ($query) {
                    $query->select('id', 'name', 'sku', 'low_stock_threshold');
                }
            ])
            ->get()
            ->filter(function ($row) {
                // filter products where balance is less than low_stock_threshold
                return $row->latestLedger && $row->product && $row->latestLedger->balance <= $row->product->low_stock_threshold;
            })
            ->map(function ($row) {
                return [
                    'product_id' => $row->product->id,
                    'name' => $row->product->name,
                    'sku' => $row->product->sku,
                    'stock_balance' => $row->latestLedger->balance,
                    'threshold' => $row->product->low_stock_threshold,
                ];
            })
            ->values();

        return ResponseHelper::success($lowStockProducts, 'Low stock products retrieved');
    }





    /**
     * @OA\Get(
     *     path="/dashboard/overview/sales-trend",
     *     tags={"Seller Dashboard"},
     *     summary="Get monthly sales vs expenses trend for the current year",
     *     description="Returns aggregated sales and expenses data for each month of the current year for the authenticated seller. Useful for building performance charts on the seller dashboard.",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Sales vs expenses by month retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sales vs expenses by month for current year"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="months",
     *                     type="array",
     *                     @OA\Items(type="string", example="Jan"),
     *                     description="List of month names from January to the current month"
     *                 ),
     *                 @OA\Property(
     *                     property="sales",
     *                     type="object",
     *                     example={"Jan": 152000, "Feb": 134000, "Mar": 98000},
     *                     description="Total sales amount per month"
     *                 ),
     *                 @OA\Property(
     *                     property="expenses",
     *                     type="object",
     *                     example={"Jan": 100000, "Feb": 125000, "Mar": 70000},
     *                     description="Total expenses amount per month"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - No valid token provided",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */

    public function salesTrend()
    {
        $sellerId = auth()->id();

        $storeIds = DB::table('stores')
            ->where('seller_id', $sellerId)
            ->pluck('id');

        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();

        $sales = DB::table('sales')
            ->select(
                DB::raw("MONTH(created_at) as month"),
                DB::raw('SUM(amount) as total')
            )
            ->whereIn('store_id', $storeIds)
            ->whereBetween('created_at', [$startOfYear, $endOfYear])
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('total', 'month');


        $expenses = DB::table('expenses')
            ->select(
                DB::raw("MONTH(created_at) as month"),
                DB::raw('SUM(amount) as total')
            )
            ->whereIn('store_id', $storeIds)
            ->whereBetween('created_at', [$startOfYear, $endOfYear])
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('total', 'month');

        $months = range(1, Carbon::now()->month);
        $salesData = [];
        $expenseData = [];
        $monthNames = [];

        foreach ($months as $m) {
            $monthName = Carbon::create()->month($m)->format('M');
            $monthNames[] = $monthName;
            $salesData[$monthName] = (float) ($sales[$m] ?? 0);
            $expenseData[$monthName] = (float) ($expenses[$m] ?? 0);
        }

        $data = [
            'months' => $monthNames,
            'sales' => $salesData,
            'expenses' => $expenseData,
        ];

        return ResponseHelper::success($data, 'Sales vs expenses by month for current year');
    }



}
