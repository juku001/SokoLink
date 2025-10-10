<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Payment;
use App\Models\Store;
use Illuminate\Http\Request;

class AdminMerchantController extends Controller
{

    /**
     * @OA\Get(
     *     path="/admin/merchants",
     *     summary="Get list of all merchants",
     *     description="Returns all merchants with optional status filtering (e.g. active, inactive, pending, blocked), including email, status, revenue, and join date",
     *     tags={"Admin"},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter merchants by status (e.g. active, inactive, pending, blocked)",
     *         @OA\Schema(type="string", example="active")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Merchant list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant List"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Best Store"),
     *                     @OA\Property(property="email", type="string", example="merchant@example.com"),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="revenue", type="number", format="float", example=12500),
     *                     @OA\Property(property="joined", type="string", format="date-time", example="2025-01-10T12:34:56")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", ref="#/components/responses/401"),
     *     @OA\Response(response=500, description="Internal server error", ref="#/components/responses/500"),
     *     security={{"sanctum": {}}}
     * )
     */
    public function index(Request $request)
    {
        $query = Store::with(['user', 'sales']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $stores = $query->get();

        $data = $stores->map(function ($store) {
            return [
                'id' => $store->id,
                'name' => $store->name,
                'email' => $store->email ?? optional($store->user)->email,
                'status' => $store->status,
                'revenue' => $store->sales()->sum('amount') ?? 0,
                'joined' => $store->created_at,
            ];
        });

        return ResponseHelper::success($data, 'Merchant List');
    }




    /**
     * @OA\Get(
     *     path="/admin/merchants/{id}",
     *     summary="Get details of a single merchant",
     *     description="Returns a merchant's full details including user info, products, reviews, sales, category, and followers",
     *     tags={"Admin"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the merchant",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Merchant details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store details"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Best Store"),
     *                 @OA\Property(property="email", type="string", example="merchant@example.com"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-10T12:34:56"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 ),
     *                 @OA\Property(
     *                     property="products",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=101),
     *                         @OA\Property(property="name", type="string", example="Product 1"),
     *                         @OA\Property(property="price", type="number", format="float", example=2500),
     *                         @OA\Property(property="quantity", type="integer", example=10)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="reviews",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=501),
     *                         @OA\Property(property="rating", type="number", format="float", example=4.5),
     *                         @OA\Property(property="comment", type="string", example="Great store!")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="sales",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1001),
     *                         @OA\Property(property="amount", type="number", format="float", example=5000),
     *                         @OA\Property(property="status", type="string", example="completed")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="category",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Electronics")
     *                 ),
     *                 @OA\Property(
     *                     property="followers",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=2001),
     *                         @OA\Property(property="name", type="string", example="Alice")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Store not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Store not found"),
     *             
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function show($id)
    {
        $store = Store::with([
            'user',
            'products',
            'reviews',
            'sales',
            'category',
            'followers'
        ])->find($id);

        if (!$store) {
            return ResponseHelper::error([], 'Store not found', 404);
        }

        return ResponseHelper::success($store, 'Store details');
    }



    /**
     * @OA\Get(
     *     path="/admin/performing/merchants",
     *     summary="Top performing merchants",
     *     description="Retrieve the top performing merchants for the current month with revenue growth compared to last month.",
     *     operationId="topPerformingMerchants",
     *     tags={"Admin"},
     *     security={{"sanctum":{}}},
     *     
     *     @OA\Response(
     *         response=200,
     *         description="List of top performing merchants",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Top 5 performing merchants this month"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="store", type="string", example="Tech Store"),
     *                     @OA\Property(property="category", type="string", example="Electronics"),
     *                     @OA\Property(property="amount", type="number", format="float", example=1250000.50),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=20.5),
     *                         @OA\Property(property="nature", type="string", example="positive"),
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

    public function top()
    {
        $now = now();
        $lastMonth = $now->copy()->subMonth();
        $topCount = 5;

        $currentMonth = Payment::selectRaw('store_id, SUM(amount) as total_amount')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->where('status', 'success')
            ->groupBy('store_id')
            ->with('store.category')
            ->get();


        $lastMonthData = Payment::selectRaw('store_id, SUM(amount) as total_amount')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->where('status', 'success')
            ->groupBy('store_id')
            ->pluck('total_amount', 'store_id');

        $growth = function ($current, $previous) {
            if ($previous == 0) {
                return $current > 0 ? 100 : 0;
            }
            return round((($current - $previous) / $previous) * 100, 2);
        };

        $stores = $currentMonth->map(function ($record) use ($lastMonthData, $growth) {
            $previous = $lastMonthData[$record->store_id] ?? 0;

            return [
                'store' => $record->store->name,
                'category' => $record->store->category->name ?? 'Uncategorized',
                'amount' => $record->total_amount,
                'percent' => [
                    'value' => $growth($record->total_amount, $previous),
                    'nature' => $record->total_amount >= $previous ? 'positive' : 'negative',
                    'duration' => 'month'
                ]
            ];
        })
            ->sortByDesc('amount')
            ->take($topCount)
            ->values();

        return ResponseHelper::success($stores, 'Top ' . $topCount . ' performing merchants this month');
    }


}
