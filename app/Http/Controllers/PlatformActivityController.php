<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Escrow;
use App\Models\Payment;
use Carbon\Carbon;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class PlatformActivityController extends Controller
{

    /**
     * @OA\Get(
     *     path="/admin/platform/overview",
     *     operationId="getPlatformOverview",
     *     tags={"Admin"},
     *     summary="Get platform overview metrics",
     *     description="Returns high-level platform statistics including merchants, active customers, revenue, and growth rate with month-over-month percentage changes.",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Platform overview retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Platform Overview"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *
     *                 @OA\Property(
     *                     property="merchants",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=120),
     *                     @OA\Property(property="percent", type="number", format="float", example=1.4)
     *                 ),
     *
     *                 @OA\Property(
     *                     property="active_customers",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=5400),
     *                     @OA\Property(property="percent", type="number", format="float", example=4.5)
     *                 ),
     *
     *                 @OA\Property(
     *                     property="revenue",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=18250.75),
     *                     @OA\Property(property="percent", type="number", format="float", example=3.5)
     *                 ),
     *
     *                 @OA\Property(
     *                     property="growth_rate",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=4.5),
     *                     @OA\Property(property="percent", type="number", format="float", example=4.5)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */

    public function overview()
    {
        $startOfThisMonth = Carbon::now()->startOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        $merchantsCurrent = Store::count();
        $merchantsLastMonth = Store::whereBetween('created_at', [
            $startOfLastMonth,
            $endOfLastMonth
        ])->count();

        $customersCurrent = User::where('role', 'buyer')->count();
        $customersLastMonth = User::where('role', 'buyer')
            ->whereBetween('created_at', [
                $startOfLastMonth,
                $endOfLastMonth
            ])->count();

        $revenueCurrent = Escrow::sum('platform_fee');
        $revenueLastMonth = Escrow::whereBetween('created_at', [
            $startOfLastMonth,
            $endOfLastMonth
        ])->sum('platform_fee');
        $growthRate = $this->percentChange($customersCurrent, $customersLastMonth);

        $data = [
            'merchants' => [
                'value' => $merchantsCurrent,
                'percent' => $this->percentChange($merchantsCurrent, $merchantsLastMonth)
            ],
            'active_customers' => [
                'value' => $customersCurrent,
                'percent' => $this->percentChange($customersCurrent, $customersLastMonth)
            ],
            'revenue' => [
                'value' => round($revenueCurrent, 2),
                'percent' => $this->percentChange($revenueCurrent, $revenueLastMonth)
            ],
            'growth_rate' => [
                'value' => $growthRate,
                'percent' => $growthRate
            ]
        ];

        return ResponseHelper::success($data, 'Platform Overview');
    }



    private function percentChange($current, $previous)
    {
        if ($previous == 0) {
            return 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }



    /**
     * @OA\Get(
     *     path="/admin/platform/operational/metrics",
     *     operationId="getOperationalMetrics",
     *     tags={"Admin"},
     *     summary="Get platform operational metrics",
     *     description="Returns real-time operational metrics such as today's transactions, system uptime, compliance score, and support tickets.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Operational metrics retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Operational Metrics"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *
     *                 @OA\Property(
     *                     property="transaction",
     *                     type="integer",
     *                     example=32,
     *                     description="Number of successful transactions today"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="system_uptime",
     *                     type="number",
     *                     format="float",
     *                     example=99.7,
     *                     description="Estimated system uptime percentage for today"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="compliance_score",
     *                     type="integer",
     *                     example=0,
     *                     description="Compliance score (reserved for future use)"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="support_tickets",
     *                     type="integer",
     *                     example=0,
     *                     description="Number of open support tickets"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */

    public function operationalMetrics()
    {


        $today = Carbon::today();

        $transactions = Payment::where('status', 'successful')
            ->whereDate('created_at', $today)
            ->count();

        $systemUptime = Cache::remember('system_uptime_today', 60, function () {
            return $this->calculateSystemUptime();
        });

        $data = [
            'transaction' => $transactions,
            'system_uptime' => $systemUptime, // percentage
            'compliance_score' => 0,
            'support_tickets' => 0
        ];

        return ResponseHelper::success($data, 'Operational Metrics');
    }

    private function calculateSystemUptime(): float
    {
        $logFile = storage_path(
            'logs/laravel-' . now()->format('Y-m-d') . '.log'
        );

        // If no log file, assume healthy
        if (!File::exists($logFile)) {
            return 99.9;
        }

        $logContent = File::get($logFile);

        $criticalErrors = substr_count($logContent, 'CRITICAL');
        $errors = substr_count($logContent, 'ERROR');

        // Weighted penalty
        $penalty = ($criticalErrors * 2) + $errors;

        // Base uptime model
        $uptime = 100 - ($penalty * 0.1);

        // Clamp values
        $uptime = max(95, min(99.9, $uptime));

        return round($uptime, 2);
    }



}
