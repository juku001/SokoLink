<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{

    public function sales()
    {
        $currentMonth = Sale::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $lastMonth = Sale::where('status', 'completed')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');

        $percent = 0;
        if ($lastMonth > 0) {
            $percent = (($currentMonth - $lastMonth) / $lastMonth) * 100;
        }

        $categoriesSales = \DB::table('sales')
            ->join('stores', 'sales.store_id', '=', 'stores.id')
            ->join('categories', 'stores.category_id', '=', 'categories.id')
            ->select('categories.id', 'categories.name', \DB::raw('SUM(sales.amount) as total_sales'))
            ->where('sales.status', 'completed')
            ->whereMonth('sales.created_at', now()->month)
            ->whereYear('sales.created_at', now()->year)
            ->groupBy('categories.id', 'categories.name')
            ->get();

        $data = [
            'total' => [
                'value' => $currentMonth,
                'percent' => round($percent, 2),
                'duration' => 'month'
            ],
            'categories' => $categoriesSales
        ];

        return ResponseHelper::success($data, "Sales volume stats");
    }



    public function users()
    {
        $total = User::where('role', '!=', 'super_admin')->count();

        $new = User::where('role', '!=', 'super_admin')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $active = User::where('role', '!=', 'super_admin')
            ->whereMonth('last_login_at', now()->month)
            ->whereYear('last_login_at', now()->year)
            ->count();

        $activeLastMonth = User::where('role', '!=', 'super_admin')
            ->whereMonth('last_login_at', now()->subMonth()->month)
            ->whereYear('last_login_at', now()->subMonth()->year)
            ->count();

        $retainedUsers = User::where('role', '!=', 'super_admin')
            ->whereMonth('last_login_at', now()->month)
            ->whereYear('last_login_at', now()->year)
            ->whereIn('id', function ($query) {
                $query->select('id')
                    ->from('users')
                    ->whereMonth('last_login_at', now()->subMonth()->month)
                    ->whereYear('last_login_at', now()->subMonth()->year);
            })
            ->count();

        $retentionRate = $activeLastMonth > 0
            ? round(($retainedUsers / $activeLastMonth) * 100, 2)
            : 0;

        $data = [
            'total' => $total,
            'active' => $active,
            'new' => $new,
            'retention_rate' => $retentionRate
        ];

        return ResponseHelper::success($data, 'User growth');
    }




    public function revenue()
    {
        $data = [
            'monthly_revenue' => $monthlyRevenue ?? 0,
            'fees' => [
                'transaction' => $transaction ?? 0,
                'subscription' => $subscription ?? 0,
                'marketing' => $marketing ?? 0
            ]
        ];

        return ResponseHelper::success($data, 'Revenue Analysis');

    }

    public function marketplace()
    {
        $data = [
            'avg_order_value' => $avgOrderValue ?? 0, //18,500
            'customer_satisfaction' => $customerSatisfaction ?? 0, //4.6/5.0
            'debt_recovery_rate' => $debt ?? 0 // 68.2%
        ];

        return ResponseHelper::success($data, 'Marketplace Health');
    }
}
