<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Payment;
use App\Models\Store;
use Illuminate\Http\Request;

class AdminMerchantController extends Controller
{
    public function index()
    {

        $stores = Store::with('user')->all();

        $data = $stores->map(function ($store) {
            return [
                'id' => $store->id,
                'name' => $store->name,
                'email' => $store->email ?? $store->user->email,
                'status' => $store->status,
                'revenue' => $store->sales->count('amount') ?? 0,
                'joined' => $store->created_at
            ];
        });

        return ResponseHelper::success($data, 'Merchant List');
    }



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
