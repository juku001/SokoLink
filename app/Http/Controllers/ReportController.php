<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;


class ReportController extends Controller
{



    /**
     * @OA\Get(
     *     path="/reports/profit/analysis",
     *     summary="Get weekly profit/loss for the current month",
     *     description="Retrieve the weekly profit or loss for the authenticated seller's stores. Profit is calculated as total sales minus total expenses per week.",
     *     operationId="profitAnalysisReport",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Weekly profit/loss report",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Weekly profit/loss for current month"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="week_1", type="number", example=2300),
     *                 @OA\Property(property="week_2", type="number", example=-3232),
     *                 @OA\Property(property="week_3", type="number", example=0),
     *                 @OA\Property(property="week_4", type="number", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */
    public function profitAnalysis()
    {
        $sellerId = auth()->id();

        $storeIds = DB::table('stores')
            ->where('seller_id', $sellerId)
            ->pluck('id');

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $sales = DB::table('sales')
            ->select(
                DB::raw("
                CASE 
                    WHEN DAY(created_at) BETWEEN 1 AND 7  THEN 'week_1'
                    WHEN DAY(created_at) BETWEEN 8 AND 14 THEN 'week_2'
                    WHEN DAY(created_at) BETWEEN 15 AND 21 THEN 'week_3'
                    ELSE 'week_4'
                END as week
            "),
                DB::raw('SUM(amount) as total')
            )
            ->whereIn('store_id', $storeIds)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('week')
            ->pluck('total', 'week');

        $expenses = DB::table('expenses')
            ->select(
                DB::raw("
                CASE 
                    WHEN DAY(created_at) BETWEEN 1 AND 7  THEN 'week_1'
                    WHEN DAY(created_at) BETWEEN 8 AND 14 THEN 'week_2'
                    WHEN DAY(created_at) BETWEEN 15 AND 21 THEN 'week_3'
                    ELSE 'week_4'
                END as week
            "),
                DB::raw('SUM(amount) as total')
            )
            ->whereIn('store_id', $storeIds)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('week')
            ->pluck('total', 'week');

        $weeks = ['week_1', 'week_2', 'week_3', 'week_4'];
        $profit = [];
        foreach ($weeks as $w) {
            $salesTotal = (float) ($sales[$w] ?? 0);
            $expenseTotal = (float) ($expenses[$w] ?? 0);
            $profit[$w] = $salesTotal - $expenseTotal; // negative = loss
        }

        return ResponseHelper::success($profit, 'Weekly profit/loss for current month');
    }




    /**
     * @OA\Get(
     *     path="/reports/sales/performance",
     *     summary="Get sales and expenses performance for the current month",
     *     description="Retrieve the total sales and total expenses of the authenticated seller's stores, grouped by week for the current month.",
     *     operationId="salesPerformanceReport",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sales and expenses report",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sales vs expenses for current month"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="sales",
     *                     type="object",
     *                     @OA\Property(property="week_1", type="number", example=23000),
     *                     @OA\Property(property="week_2", type="number", example=343400),
     *                     @OA\Property(property="week_3", type="number", example=0),
     *                     @OA\Property(property="week_4", type="number", example=0)
     *                 ),
     *                 @OA\Property(
     *                     property="expenses",
     *                     type="object",
     *                     @OA\Property(property="week_1", type="number", example=15000),
     *                     @OA\Property(property="week_2", type="number", example=123400),
     *                     @OA\Property(property="week_3", type="number", example=0),
     *                     @OA\Property(property="week_4", type="number", example=0)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */
    public function salesPerformance()
    {
        $sellerId = auth()->id();

        $storeIds = DB::table('stores')
            ->where('seller_id', $sellerId)
            ->pluck('id');

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $sales = DB::table('sales')
            ->select(
                DB::raw("
                CASE 
                  WHEN DAY(created_at) BETWEEN 1 AND 7  THEN 'week_1'
                  WHEN DAY(created_at) BETWEEN 8 AND 14 THEN 'week_2'
                  WHEN DAY(created_at) BETWEEN 15 AND 21 THEN 'week_3'
                  ELSE 'week_4'
                END as week
            "),
                DB::raw('SUM(amount) as total')
            )
            ->whereIn('store_id', $storeIds)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('week')
            ->pluck('total', 'week');

        $expenses = DB::table('expenses')
            ->select(
                DB::raw("
                CASE 
                  WHEN DAY(created_at) BETWEEN 1 AND 7  THEN 'week_1'
                  WHEN DAY(created_at) BETWEEN 8 AND 14 THEN 'week_2'
                  WHEN DAY(created_at) BETWEEN 15 AND 21 THEN 'week_3'
                  ELSE 'week_4'
                END as week
            "),
                DB::raw('SUM(amount) as total')
            )
            ->whereIn('store_id', $storeIds)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('week')
            ->pluck('total', 'week');

        $weeks = ['week_1', 'week_2', 'week_3', 'week_4'];
        $salesData = [];
        $expenseData = [];
        foreach ($weeks as $w) {
            $salesData[$w] = (float) ($sales[$w] ?? 0);
            $expenseData[$w] = (float) ($expenses[$w] ?? 0);
        }

        $data = [
            'sales' => $salesData,
            'expenses' => $expenseData,
        ];

        return ResponseHelper::success($data, 'Sales vs expenses for current month');
    }









    /**
     * @OA\Get(
     *     path="/reports/credit/score",
     *     summary="Get monthly credit score trend for the seller",
     *     description="Retrieve the monthly credit score, revenue, orders, refunds, and late shipments for the authenticated seller from January to the current month.",
     *     operationId="creditScoreReport",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Monthly credit score trend",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Monthly credit score trend"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="month", type="string", example="2025-01"),
     *                     @OA\Property(property="month_abbr", type="string", example="Jan"),
     *                     @OA\Property(property="credit_score", type="integer", example=85),
     *                     @OA\Property(property="total_revenue", type="number", format="float", example=125000),
     *                     @OA\Property(property="orders", type="integer", example=42),
     *                     @OA\Property(property="refunds", type="integer", example=2),
     *                     @OA\Property(property="late_shipments", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */
    public function creditScore()
    {
        $sellerId = auth()->id();
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfMonth();

        $months = [];
        $cursor = $startOfYear->copy();

        while ($cursor <= $endOfYear) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();

            $totalRevenue = DB::table('sales')
                ->where('seller_id', $sellerId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $totalOrders = DB::table('sales')
                ->where('seller_id', $sellerId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $refunds = DB::table('sales')
                ->where('seller_id', $sellerId)
                ->where('status', 'refunded')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $lateShipments = DB::table('shipments')
                ->where('seller_id', $sellerId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('delivered_late', true)
                ->count();

            $revenueScore = min($totalRevenue / 100000, 1) * 40;
            $orderScore = min($totalOrders / 500, 1) * 30;
            $refundPenalty = min($refunds * 5, 20);
            $latePenalty = min($lateShipments * 2, 10);

            $score = max(0, round($revenueScore + $orderScore - $refundPenalty - $latePenalty));

            $months[] = [
                'month' => $monthStart->format('Y-m'),
                'month_abbr' => $monthStart->format('M'),
                'credit_score' => $score,
                'total_revenue' => $totalRevenue,
                'orders' => $totalOrders,
                'refunds' => $refunds,
                'late_shipments' => $lateShipments,
            ];

            $cursor->addMonth();
        }

        return ResponseHelper::success($months, 'Monthly credit score trend');
    }





    /**
     * @OA\Get(
     *     path="/reports/inventory",
     *     summary="Inventory health report",
     *     description="Provides a summary of current stock levels and inventory value per product category for the authenticated seller.",
     *     operationId="getInventoryReport",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Inventory report retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Inventory health report"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="stock_levels",
     *                     type="object",
     *                     example={"Electronics": 250, "Clothing": 120},
     *                     description="Total quantity in stock for each category"
     *                 ),
     *                 @OA\Property(
     *                     property="inventory_value",
     *                     type="object",
     *                     example={"Electronics": 45500.75, "Clothing": 8700.00},
     *                     description="Total inventory value (quantity × price) per category"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */

    public function inventory()
    {
        $sellerId = auth()->id();

        // $rows = DB::table('products')
        //     ->join('stores', 'products.store_id', '=', 'stores.id')
        //     ->join('product_categories', 'products.id', '=', 'product_categories.product_id')
        //     ->join('categories', 'product_categories.category_id', '=', 'categories.id')
        //     ->where('stores.seller_id', $sellerId)
        //     ->groupBy('categories.name')
        //     ->select(
        //         'categories.name as category',
        //         DB::raw('SUM(products.quantity) as stock_level'),
        //         DB::raw('SUM(products.quantity * products.price) as inventory_value')
        //     )
        //     ->get();
        $rows = DB::table('products')
            ->join('stores', 'products.store_id', '=', 'stores.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('stores.seller_id', $sellerId)
            ->groupBy('categories.id', 'categories.name')
            ->select(
                'categories.name as category',
                DB::raw('SUM(products.quantity) as stock_level'),
                DB::raw('SUM(products.quantity * products.price) as inventory_value')
            )
            ->get();


        $stockLevels = [];
        $inventoryValues = [];
        foreach ($rows as $row) {
            $stockLevels[$row->category] = (int) $row->stock_level;
            $inventoryValues[$row->category] = (float) $row->inventory_value;
        }

        $data = [
            'stock_levels' => $stockLevels,
            'inventory_value' => $inventoryValues
        ];

        return ResponseHelper::success($data, 'Inventory health report');
    }



}
