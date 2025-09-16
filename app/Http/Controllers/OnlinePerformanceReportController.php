<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class OnlinePerformanceReportController extends Controller
{



    /**
     * @OA\Get(
     *     path="/reports/online/performance/stats",
     *     summary="Get monthly online store performance statistics",
     *     description="Returns key metrics for the authenticated seller’s store during the current month, 
     *     including visits, cart additions, conversion rate, and cart-abandonment percentage, plus month-over-month change for visits.",
     *     operationId="onlinePerformanceStats",
     *     tags={"Reports"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Store performance metrics for the current month",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store performance metrics for the current month"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="visits",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=1500),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=12.5),
     *                         @OA\Property(property="nature", type="string", example="positive", description="positive|negative"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="cart_additions",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=320),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=0),
     *                         @OA\Property(property="nature", type="string", example="positive"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="conversion_rate",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=4.73),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=0),
     *                         @OA\Property(property="nature", type="string", example="neutral"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="cart_abandonment",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=37.5),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=0),
     *                         @OA\Property(property="nature", type="string", example="neutral"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
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

    public function index()
    {
        $sellerId = auth()->id();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $visitsCurrent = DB::table('store_visits')
            ->where('seller_id', $sellerId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $visitsPrevious = DB::table('store_visits')
            ->where('seller_id', $sellerId)
            ->whereBetween('created_at', [
                $startOfMonth->copy()->subMonth(),
                $endOfMonth->copy()->subMonth()
            ])
            ->count();

        $cartAdditions = DB::table('cart_items')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereHas('product.store', fn($q) => $q->where('seller_id', $sellerId))
            ->count();

        $orders = DB::table('orders')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereHas('store', fn($q) => $q->where('seller_id', $sellerId))
            ->count();

        $abandonedCarts = DB::table('carts')
            ->whereHas('items.product.store', fn($q) => $q->where('seller_id', $sellerId))
            ->whereDoesntHave('order')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $percentChange = function ($current, $previous) {
            if ($previous == 0) {
                return $current > 0 ? 100 : 0;
            }
            return round((($current - $previous) / $previous) * 100, 1);
        };

        $data = [
            'visits' => [
                'value' => $visitsCurrent,
                'percent' => [
                    'value' => $percentChange($visitsCurrent, $visitsPrevious),
                    'nature' => $visitsCurrent >= $visitsPrevious ? 'positive' : 'negative',
                    'duration' => 'month'
                ]
            ],
            'cart_additions' => [
                'value' => $cartAdditions,
                'percent' => [
                    'value' => 0, // add previous-month compare if desired
                    'nature' => 'positive',
                    'duration' => 'month'
                ]
            ],
            'conversion_rate' => [
                'value' => $visitsCurrent ? round(($orders / $visitsCurrent) * 100, 2) : 0,
                'percent' => [
                    'value' => 0, // same idea for comparison
                    'nature' => 'neutral',
                    'duration' => 'month'
                ]
            ],
            'cart_abandonment' => [
                'value' => $cartAdditions
                    ? round(($abandonedCarts / $cartAdditions) * 100, 2)
                    : 0,
                'percent' => [
                    'value' => 0,
                    'nature' => 'neutral',
                    'duration' => 'month'
                ]
            ]
        ];

        return ResponseHelper::success($data, 'Store performance metrics for the current month');
    }




    /**
     * @OA\Get(
     *     path="/reports/online/performance/store/activity",
     *     summary="Get weekly store activity metrics for the current month",
     *     description="Returns per-week counts of visits, impressions, and engagements for the authenticated seller’s store during the current month.",
     *     operationId="onlinePerformanceStoreActivity",
     *     tags={"Reports"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Weekly store activity data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store activity for the current month by week"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="week_1",
     *                     type="object",
     *                     @OA\Property(property="visits", type="integer", example=120),
     *                     @OA\Property(property="impressions", type="integer", example=340),
     *                     @OA\Property(property="engagements", type="integer", example=75)
     *                 ),
     *                 @OA\Property(
     *                     property="week_2",
     *                     type="object",
     *                     @OA\Property(property="visits", type="integer", example=95),
     *                     @OA\Property(property="impressions", type="integer", example=280),
     *                     @OA\Property(property="engagements", type="integer", example=60)
     *                 ),
     *                 @OA\Property(
     *                     property="week_3",
     *                     type="object",
     *                     @OA\Property(property="visits", type="integer", example=110),
     *                     @OA\Property(property="impressions", type="integer", example=300),
     *                     @OA\Property(property="engagements", type="integer", example=68)
     *                 ),
     *                 @OA\Property(
     *                     property="week_4",
     *                     type="object",
     *                     @OA\Property(property="visits", type="integer", example=150),
     *                     @OA\Property(property="impressions", type="integer", example=400),
     *                     @OA\Property(property="engagements", type="integer", example=90)
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

    public function activity()
    {
        $sellerId = auth()->id();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $countBetween = function (string $table, $start, $end) use ($sellerId) {
            return DB::table($table)
                ->where('seller_id', $sellerId)
                ->whereBetween('created_at', [$start, $end])
                ->count();
        };

        $weeks = [];
        $cursor = $startOfMonth->copy();
        for ($i = 1; $i <= 4; $i++) {
            $weekStart = $cursor->copy();
            $weekEnd = $cursor->copy()->addWeek()->subSecond();

            if ($weekEnd->gt($endOfMonth)) {
                $weekEnd = $endOfMonth->copy();
            }

            $weeks["week_{$i}"] = [
                'visits' => $countBetween('store_visits', $weekStart, $weekEnd),
                'impressions' => $countBetween('store_impressions', $weekStart, $weekEnd),
                'engagements' => $countBetween('store_engagements', $weekStart, $weekEnd),
            ];

            $cursor = $weekEnd->addSecond();
            if ($cursor->gt($endOfMonth))
                break;
        }

        return ResponseHelper::success($weeks, 'Store activity for the current month by week');
    }



    /**
     * @OA\Get(
     *     path="/reports/online/performance/conversion/rate",
     *     summary="Get monthly conversion rate trends",
     *     description="Returns month-by-month store visits, completed orders, and conversion rate percentages for the authenticated seller from the start of the current year through the current month.",
     *     operationId="reportsConversionRate",
     *     tags={"Reports"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Monthly conversion rate data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Monthly conversion rate trends"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="month", type="string", example="2025-01", description="Year-month in YYYY-MM format"),
     *                     @OA\Property(property="month_abbr", type="string", example="Jan", description="Three-letter month abbreviation"),
     *                     @OA\Property(property="visits", type="integer", example=450, description="Number of store visits in the month"),
     *                     @OA\Property(property="orders", type="integer", example=120, description="Number of completed orders in the month"),
     *                     @OA\Property(property="conversion_rate", type="number", format="float", example=26.67, description="(orders/visits)*100 rounded to 2 decimals")
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


    public function conversion()
    {
        $sellerId = auth()->id();
        $startYear = Carbon::now()->startOfYear();
        $endMonth = Carbon::now()->endOfMonth();

        $months = [];
        $cursor = $startYear->copy();

        while ($cursor->lte($endMonth)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();


            $visits = DB::table('store_visits')
                ->where('seller_id', $sellerId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $orders = DB::table('orders')
                ->where('seller_id', $sellerId)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $rate = $visits > 0 ? round(($orders / $visits) * 100, 2) : 0;

            $months[] = [
                'month' => $monthStart->format('Y-m'),
                'month_abbr' => $monthStart->format('M'),   // e.g. Jan
                'visits' => $visits,
                'orders' => $orders,
                'conversion_rate' => $rate,
            ];

            $cursor->addMonth();
        }

        return ResponseHelper::success($months, 'Monthly conversion rate trends');
    }





    /**
     * @OA\Get(
     *     path="/reports/online/performance/top/performing",
     *     summary="Get top performing products",
     *     description="Returns the top 10 products for the authenticated seller, ranked by combined clicks and completed purchases.",
     *     operationId="getTopPerformingProducts",
     *     tags={"Reports"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Top performing products retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Top performing products (clicks & purchases)"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=42, description="Product ID"),
     *                     @OA\Property(property="name", type="string", example="Wireless Headphones", description="Product name"),
     *                     @OA\Property(property="clicks", type="integer", example=350, description="Total number of clicks"),
     *                     @OA\Property(property="purchases", type="integer", example=75, description="Total completed purchases")
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
    public function topPerformance()
    {
        $sellerId = auth()->id();

        // ---- Clicks per product -------------------------------------------
        // Assuming you have a table `product_clicks` with: product_id, created_at
        $clicks = DB::table('product_clicks')
            ->select('product_id', DB::raw('COUNT(*) as total_clicks'))
            ->whereIn('product_id', function ($q) use ($sellerId) {
                $q->select('id')
                    ->from('products')
                    ->where('store_id', function ($sub) use ($sellerId) {
                        $sub->select('id')->from('stores')->where('seller_id', $sellerId);
                    });
            })
            ->groupBy('product_id');

        // ---- Purchases per product ----------------------------------------
        // Assuming `order_items` with: product_id, quantity and
        // related `orders` table to filter by seller_id & completed status.
        $purchases = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) as total_purchases'))
            ->where('orders.status', 'completed')
            ->whereIn('order_items.product_id', function ($q) use ($sellerId) {
                $q->select('id')
                    ->from('products')
                    ->where('store_id', function ($sub) use ($sellerId) {
                        $sub->select('id')->from('stores')->where('seller_id', $sellerId);
                    });
            })
            ->groupBy('order_items.product_id');

        // ---- Combine clicks and purchases ---------------------------------
        $results = DB::table('products')
            ->leftJoinSub($clicks, 'c', 'products.id', '=', 'c.product_id')
            ->leftJoinSub($purchases, 'p', 'products.id', '=', 'p.product_id')
            ->whereIn('products.store_id', function ($q) use ($sellerId) {
                $q->select('id')->from('stores')->where('seller_id', $sellerId);
            })
            ->select(
                'products.id',
                'products.name',
                DB::raw('COALESCE(c.total_clicks, 0) as clicks'),
                DB::raw('COALESCE(p.total_purchases, 0) as purchases')
            )
            ->orderByDesc(DB::raw('COALESCE(p.total_purchases, 0) + COALESCE(c.total_clicks, 0)'))
            ->limit(10)   // top 10 products
            ->get();

        return ResponseHelper::success($results, 'Top performing products (clicks & purchases)');
    }





    /**
     * @OA\Get(
     *     path="/reports/online/performance/products",
     *     summary="Product performance statistics",
     *     description="Returns per-product performance for the authenticated seller, including clicks, purchases, conversion rate, and total revenue.",
     *     operationId="getProductPerformance",
     *     tags={"Reports"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product performance stats retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product performance stats"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=101, description="Product ID"),
     *                     @OA\Property(property="name", type="string", example="Wireless Earbuds", description="Product name"),
     *                     @OA\Property(property="page_clicks", type="integer", example=450, description="Total page clicks for the product"),
     *                     @OA\Property(property="purchases", type="integer", example=120, description="Total completed purchases"),
     *                     @OA\Property(property="conversion_rate", type="number", format="float", example=26.7, description="Purchases ÷ Clicks × 100"),
     *                     @OA\Property(property="revenue", type="number", format="float", example=5890.50, description="Total revenue generated from completed orders")
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
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */

    public function product()
    {
        $sellerId = auth()->id();

        // 1) Total clicks per product
        $clicks = DB::table('product_clicks')
            ->select('product_id', DB::raw('COUNT(*) as total_clicks'))
            ->whereIn('product_id', function ($q) use ($sellerId) {
                $q->select('id')
                    ->from('products')
                    ->where('store_id', function ($sub) use ($sellerId) {
                        $sub->select('id')
                            ->from('stores')
                            ->where('seller_id', $sellerId);
                    });
            })
            ->groupBy('product_id');

        // 2) Purchases + revenue per product
        $purchases = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as total_purchases'),
                DB::raw('SUM(order_items.quantity * order_items.price) as total_revenue')
            )
            ->where('orders.status', 'completed')
            ->whereIn('order_items.product_id', function ($q) use ($sellerId) {
                $q->select('id')
                    ->from('products')
                    ->where('store_id', function ($sub) use ($sellerId) {
                        $sub->select('id')
                            ->from('stores')
                            ->where('seller_id', $sellerId);
                    });
            })
            ->groupBy('order_items.product_id');

        // 3) Combine and calculate conversion
        $results = DB::table('products')
            ->leftJoinSub($clicks, 'c', 'products.id', '=', 'c.product_id')
            ->leftJoinSub($purchases, 'p', 'products.id', '=', 'p.product_id')
            ->whereIn('products.store_id', function ($q) use ($sellerId) {
                $q->select('id')->from('stores')->where('seller_id', $sellerId);
            })
            ->select(
                'products.id',
                'products.name',
                DB::raw('COALESCE(c.total_clicks, 0) as page_clicks'),
                DB::raw('COALESCE(p.total_purchases, 0) as purchases'),
                DB::raw('ROUND(
                CASE WHEN COALESCE(c.total_clicks,0) > 0
                     THEN (COALESCE(p.total_purchases,0) / COALESCE(c.total_clicks,0)) * 100
                     ELSE 0 END, 1
            ) as conversion_rate'),
                DB::raw('COALESCE(p.total_revenue, 0) as revenue')
            )
            ->orderByDesc('p.total_revenue')
            ->get();

        return ResponseHelper::success($results, 'Product performance stats');
    }

}
