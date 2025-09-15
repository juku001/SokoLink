<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\AcademyLesson;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{


    public function seller()
    {

    }


    /**
     * @OA\Get(
     *   tags={"Dashboard"},
     *   path="/dashboard/contacts",
     *   summary="Get Contact Stats",
     *   @OA\Response(
     *     response=200, 
     *     description="OK",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Contact Dashboard Stats"),
     *       @OA\Property(property="code", type="integer", example=200),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(
     *           property="total",
     *           type="integer",
     *           example=9
     *         ),
     *          @OA\Property(
     *           property="clients",
     *           type="integer",
     *           example=2
     *         ),
     *          @OA\Property(
     *           property="customers",
     *           type="integer",
     *           example=4
     *         ),
     *          @OA\Property(
     *           property="suppliers",
     *           type="integer",
     *           example=3
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized", ref="#/components/responses/401"),
     * )
     */

    public function contacts()
    {

        $id = auth()->user()->id;

        $stats = Contact::selectRaw("type, COUNT(*) as count")
            ->where('user_id', $id)
            ->groupBy('type')
            ->pluck('count', 'type');

        $data = [
            'total' => $stats->sum(),
            'clients' => $stats['client'] ?? 0,
            'customers' => $stats['customer'] ?? 0,
            'suppliers' => $stats['supplier'] ?? 0,
        ];

        return ResponseHelper::success($data, 'Contact Dashboard Stats');
    }

    public function academy()
    {
        $videos = AcademyLesson::count();
        $data = [
            'videos' => $videos ?? 0,
            'ratings' => $ratings ?? 0,
            'students' => $students ?? 0,
            'content' => [
                'state' => 'Free',
                'message' => 'All Content'
            ]
        ];
    }



    /**
     * @OA\Get(
     *     path="/dashboard/expenses",
     *     tags={"Dashboard"},
     *     summary="Get expense statistics for the dashboard",
     *     description="Retrieve monthly totals, pending expenses, and average daily expenses for the authenticated seller",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Expenses dashboard stats retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expenses dashboard stats"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="total",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=1250.50),
     *                     @OA\Property(property="percent", type="number", format="float", example=12.5)
     *                 ),
     *                 @OA\Property(
     *                     property="pending",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=250.00),
     *                     @OA\Property(property="count", type="integer", example=3)
     *                 ),
     *                 @OA\Property(
     *                     property="average_daily",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=41.67)
     *                 )
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error: Database error or exception message"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */

    public function expenses()
    {
        $authId = auth()->id();

        // Current month and last month
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Total for current month
        $total = Expense::where('seller_id', $authId)
            ->whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Total for last month
        $lastMonthTotal = Expense::where('seller_id', $authId)
            ->whereBetween('expense_date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');

        // Percent change from last month
        $percentChange = $lastMonthTotal > 0
            ? round((($total - $lastMonthTotal) / $lastMonthTotal) * 100, 1)
            : 100;

        // Pending expenses
        $pendingQuery = Expense::where('seller_id', $authId)->where('status', 'pending');
        $pending = $pendingQuery->sum('amount');
        $pendingCount = $pendingQuery->count();

        // Average daily (this month)
        $daysElapsed = $now->day; // days passed this month
        $avg = $daysElapsed > 0 ? round($total / $daysElapsed, 2) : 0;

        $data = [
            'total' => [
                'value' => $total ?? 0,
                'percent' => $percentChange // from last month
            ],
            'pending' => [
                'value' => $pending ?? 0,
                'count' => $pendingCount ?? 0
            ],
            'average_daily' => [
                'value' => $avg ?? 0,
            ]
        ];

        return ResponseHelper::success($data, 'Expenses dashboard stats');
    }



    public function adminCustomerManagement()
    {
        // Total buyers
        $totalBuyers = User::where('role', 'buyer')->count();

        // Active buyers
        $activeBuyers = User::where('role', 'buyer')->where('status', 'active')->count();

        // Last month buyers
        $lastMonthBuyers = User::where('role', 'buyer')
            ->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ])
            ->count();


        $thisMonthBuyers = User::where('role', 'buyer')
            ->whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ])
            ->count();


        $growthRate = $lastMonthBuyers > 0
            ? (($thisMonthBuyers - $lastMonthBuyers) / $lastMonthBuyers) * 100
            : 100;


        $totalDiff = $lastMonthBuyers > 0
            ? (($totalBuyers - $lastMonthBuyers) / $lastMonthBuyers) * 100
            : 100;

        $data = [
            'total' => [
                'value' => $totalBuyers,
                'percent' => [
                    'value' => round(abs($totalDiff), 2),
                    'nature' => $totalDiff >= 0 ? 'positive' : 'negative',
                    'duration' => 'month'
                ]
            ],
            'active' => [
                'value' => $activeBuyers,
                'percent' => [
                    'value' => 0, // you can compute change similar to total if you track last month active users
                    'nature' => 'neutral',
                    'duration' => 'month'
                ]
            ],
            'growth' => [
                'value' => round($growthRate, 2),
                'percent' => [
                    'value' => abs(round($growthRate, 2)),
                    'nature' => $growthRate >= 0 ? 'positive' : 'negative',
                    'duration' => 'month'
                ]
            ],
            'tickets' => [
                'value' => 0, // placeholder until you implement tickets
                'percent' => [
                    'value' => 0,
                    'nature' => 'neutral',
                    'duration' => 'month'
                ]
            ]
        ];

        return ResponseHelper::success($data, 'Customer dashboard information');
    }



    public function platformHealth()
    {
        // Uptime
        $uptimeSeconds = @shell_exec('cat /proc/uptime');
        $uptimeSeconds = explode(" ", $uptimeSeconds)[0] ?? 0;
        $uptimeHours = round($uptimeSeconds / 3600, 2);

        // Response time
        $responseTimes = Cache::get('response_time', []);
        $responseTime = count($responseTimes) ? round(array_sum($responseTimes) / count($responseTimes), 2) : 0;

        // Database load
        $dbLoad = \DB::select("SHOW STATUS LIKE 'Threads_connected'");
        $databaseLoad = $dbLoad[0]->Value ?? 0;

        // API load
        $apiLoad = Cache::get('api_requests', 0);
        Cache::put('api_requests', 0, now()->addMinute());

        $data = [
            'system_uptime' => [
                'value' => $uptimeHours,
                'unit' => 'hours',
                'percent' => ['value' => 99.98, 'nature' => 'positive', 'duration' => 'day']
            ],
            'response_time' => [
                'value' => $responseTime,
                'unit' => 'ms',
                'percent' => ['value' => 0.02, 'nature' => 'positive', 'duration' => 'hour']
            ],
            'database_load' => [
                'value' => $databaseLoad,
                'unit' => 'connections',
                'percent' => ['value' => $databaseLoad > 80 ? 10 : 2, 'nature' => $databaseLoad > 80 ? 'negative' : 'positive', 'duration' => 'hour']
            ],
            'api_load' => [
                'value' => $apiLoad,
                'unit' => 'req/min',
                'percent' => ['value' => $apiLoad > 100 ? 20 : 5, 'nature' => $apiLoad > 100 ? 'negative' : 'positive', 'duration' => 'minute']
            ]
        ];

        return ResponseHelper::success($data, 'Platform Health Dashboard');
    }



    public function adminPayments()
    {
        // Total payments this month
        $total = Payment::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        // Successful payments this month
        $successfulPayments = Payment::where('status', 'success')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Failed payments this month
        $failedPayments = Payment::where('status', 'failed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Total payment attempts this month
        $totalPayments = $successfulPayments + $failedPayments;

        // Success rate (avoid division by zero)
        $successRate = $totalPayments > 0
            ? round(($successfulPayments / $totalPayments) * 100, 2)
            : 0;

        // Revenue last month
        $lastMonthTotal = Payment::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');

        // Revenue growth % compared to last month
        $revenueGrowth = $lastMonthTotal > 0
            ? round((($total - $lastMonthTotal) / $lastMonthTotal) * 100, 2)
            : 0;

        // Positive/negative nature
        $growthNature = $revenueGrowth >= 0 ? 'positive' : 'negative';

        $data = [
            'total' => [
                'value' => $total,
                'percent' => [
                    'value' => $revenueGrowth,
                    'nature' => $growthNature,
                    'duration' => 'month'
                ]
            ],
            'success_rate' => [
                'value' => $successRate,
                'percent' => [
                    'value' => $successRate,
                    'nature' => $successRate >= 50 ? 'positive' : 'negative',
                    'duration' => 'month'
                ]
            ],
            'failed_payments' => [
                'value' => $failedPayments,
                'percent' => [
                    'value' => $failedPayments, // you could also compare with last month
                    'nature' => $failedPayments > 0 ? 'negative' : 'positive',
                    'duration' => 'month'
                ]
            ],
            'revenue_growth' => [
                'value' => $revenueGrowth,
                'percent' => [
                    'value' => abs($revenueGrowth),
                    'nature' => $growthNature,
                    'duration' => 'month'
                ]
            ]
        ];

        return ResponseHelper::success($data, 'Admin Payment Stats');
    }



    public function merchants()
    {
        $now = now();
        $lastMonth = $now->copy()->subMonth();

        $total = Store::count();
        $totalLastMonth = Store::whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();

        $active = Store::where('status', 'active')->count();
        $activeLastMonth = Store::where('status', 'active')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();

        $pending = Store::where('status', 'pending')->count();
        $pendingLastMonth = Store::where('status', 'pending')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();

        $suspended = Store::where('status', 'suspended')->count();
        $suspendedLastMonth = Store::where('status', 'suspended')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();

        $growth = function ($current, $previous) {
            if ($previous == 0) {
                return $current > 0 ? 100 : 0;
            }
            return round((($current - $previous) / $previous) * 100, 2);
        };

        $data = [
            'total' => [
                'value' => $total,
                'percent' => [
                    'value' => $growth($total, $totalLastMonth),
                    'nature' => $total >= $totalLastMonth ? 'positive' : 'negative',
                    'duration' => 'month'
                ]
            ],
            'active' => [
                'value' => $active,
                'percent' => [
                    'value' => $growth($active, $activeLastMonth),
                    'nature' => $active >= $activeLastMonth ? 'positive' : 'negative',
                    'duration' => 'month'
                ]
            ],
            'pending' => [
                'value' => $pending,
                'percent' => [
                    'value' => $growth($pending, $pendingLastMonth),
                    'nature' => $pending >= $pendingLastMonth ? 'positive' : 'negative',
                    'duration' => 'month'
                ]
            ],
            'suspended' => [
                'value' => $suspended,
                'percent' => [
                    'value' => $growth($suspended, $suspendedLastMonth),
                    'nature' => $suspended >= $suspendedLastMonth ? 'positive' : 'negative',
                    'duration' => 'month'
                ]
            ],
        ];

        return ResponseHelper::success($data, 'Merchant Dashboard Stats');
    }







}
