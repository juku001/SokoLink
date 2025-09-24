<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\AcademyLesson;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{




    /**
     * @OA\Get(
     *   tags={"Seller Dashboard"},
     *   path="/dashboard/contacts/stats",
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



    /**
     * @OA\Get(
     *     path="/dashboard/academy/stats",
     *     summary="Academy Statistics ",
     *     description="Returns overall stats for academy lessons.",
     *     tags={"Seller Dashboard"},
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Academy statistics"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="videos", type="integer", example=42),
     *                 @OA\Property(property="ratings", type="number", format="float", example=4.5),
     *                 @OA\Property(property="students", type="integer", example=1200),
     *                 @OA\Property(property="content", type="object",
     *                     @OA\Property(property="state", type="string", example="Free"),
     *                     @OA\Property(property="message", type="string", example="All Content")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       ref="#/components/responses/401"
     *     ),
     *      @OA\Response(
     *       response=403,
     *       description="Unauthorized",
     *       ref="#/components/responses/403"
     *     )
     * )
     */

    public function academy()
    {
        $videos = AcademyLesson::count();

        $ratings = AcademyLesson::avg('rating') ?? 0;

        $students = AcademyLesson::sum('student_count') ?? 0;

        $data = [
            'videos' => $videos,
            'ratings' => round($ratings, 1),
            'students' => $students,
            'content' => [
                'state' => 'Free',
                'message' => 'All Content',
            ],
        ];

        return ResponseHelper::success($data, 'Academy statistics');
    }




    /**
     * @OA\Get(
     *     path="/dashboard/expenses/stats",
     *     tags={"Seller Dashboard"},
     *     summary="Get expense statistics for the dashboard",
     *     description="Retrieve monthly totals, pending expenses, and average daily expenses for the authenticated seller",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Expenses dashboard stats retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expenses dashboard stats"),
     *             @OA\Property(property="code", type="integer", example=200),
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
     *         ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         ref="#/components/responses/500"
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




    /**
     * @OA\Get(
     *     path="/dashboard/admin/customers",
     *     summary="Admin customer management dashboard",
     *     description="Returns key buyer statistics for the admin dashboard, including totals, active buyers, and growth trends.",
     *     operationId="adminCustomerManagement",
     *     tags={"Admin Dashboard"},
     *     security={{"sanctum":{}}},  
     *     @OA\Response(
     *         response=200,
     *         description="Customer dashboard information",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer dashboard information"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="total",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=1200, description="Total number of buyers"),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=15.5, description="Percentage change vs last month"),
     *                         @OA\Property(property="nature", type="string", example="positive", description="positive|negative"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="active",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=950, description="Active buyers count"),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=0),
     *                         @OA\Property(property="nature", type="string", example="neutral"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="growth",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=20.0, description="Growth rate compared to last month"),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=20.0),
     *                         @OA\Property(property="nature", type="string", example="positive"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="tickets",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=0, description="Support tickets placeholder"),
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
     *
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




    /**
     * @OA\Get(
     *     path="/dashboard/admin/platform/health",
     *     summary="Platform health dashboard",
     *     description="Provides real-time metrics on system uptime, average response time, database connections, and API request load for platform monitoring.",
     *     operationId="platformHealth",
     *     tags={"Admin Dashboard"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Platform health statistics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Platform Health Dashboard"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="system_uptime",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=124.5, description="System uptime in hours"),
     *                     @OA\Property(property="unit", type="string", example="hours"),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=99.98),
     *                         @OA\Property(property="nature", type="string", example="positive"),
     *                         @OA\Property(property="duration", type="string", example="day")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="response_time",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=120.4, description="Average API response time in ms"),
     *                     @OA\Property(property="unit", type="string", example="ms"),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=0.02),
     *                         @OA\Property(property="nature", type="string", example="positive"),
     *                         @OA\Property(property="duration", type="string", example="hour")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="database_load",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=45, description="Active DB connections"),
     *                     @OA\Property(property="unit", type="string", example="connections"),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=2),
     *                         @OA\Property(property="nature", type="string", example="positive"),
     *                         @OA\Property(property="duration", type="string", example="hour")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="api_load",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=75, description="Requests per minute"),
     *                     @OA\Property(property="unit", type="string", example="req/min"),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=5),
     *                         @OA\Property(property="nature", type="string", example="positive"),
     *                         @OA\Property(property="duration", type="string", example="minute")
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



    /**
     * @OA\Get(
     *     path="/dashboard/admin/payments",
     *     summary="Admin payments dashboard statistics",
     *     description="Provides monthly payment analytics such as total revenue, success rate, failed payments, and revenue growth compared to the previous month.",
     *     operationId="adminPayments",
     *     tags={"Admin Dashboard"},
     *     security={{"sanctum":{}}},  
     *     @OA\Response(
     *         response=200,
     *         description="Admin payment stats",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Admin Payment Stats"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="total",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=45200.75, description="Total revenue for the current month"),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=12.5, description="Growth vs. last month in percentage"),
     *                         @OA\Property(property="nature", type="string", example="positive", description="positive|negative"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="success_rate",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=87.3, description="Percentage of successful payment attempts this month"),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=87.3),
     *                         @OA\Property(property="nature", type="string", example="positive"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="failed_payments",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=15, description="Number of failed payment attempts this month"),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="integer", example=15),
     *                         @OA\Property(property="nature", type="string", example="negative"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="revenue_growth",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=8.4, description="Revenue growth rate compared to previous month (%)"),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=8.4),
     *                         @OA\Property(property="nature", type="string", example="positive"),
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
     *         response=500,
     *         description="Server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */
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




    /**
     * @OA\Get(
     *     path="/dashboard/admin/merchants",
     *     summary="Admin merchants dashboard statistics",
     *     description="Returns monthly merchant (store) metrics including total, active, pending, and suspended stores with growth percentages compared to the previous month.",
     *     operationId="adminMerchants",
     *     tags={"Admin Dashboard"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Merchant dashboard stats",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant Dashboard Stats"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="total",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=320, description="Total number of stores"),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=12.5, description="Growth vs last month (%)"),
     *                         @OA\Property(property="nature", type="string", example="positive", description="positive|negative"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="active",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=250, description="Number of active stores"),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=8.4),
     *                         @OA\Property(property="nature", type="string", example="positive"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="pending",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=40, description="Number of stores pending approval"),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=-5.2),
     *                         @OA\Property(property="nature", type="string", example="negative"),
     *                         @OA\Property(property="duration", type="string", example="month")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="suspended",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=30, description="Number of suspended stores"),
     *                     @OA\Property(
     *                         property="percent",
     *                         type="object",
     *                         @OA\Property(property="value", type="number", format="float", example=3.0),
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




    /**
     * @OA\Get(
     *     path="/dashboard/products/stats",
     *     summary="Get product statistics for the authenticated seller",
     *     description="Returns counts of total products, published products, low-stock products, and total inventory value for the seller’s active store.",
     *     operationId="getProductStats",
     *     tags={"Seller Dashboard"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="true"),
     *             @OA\Property(property="message", type="string", example="Product statistics fetched successfully"),
     *           @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_products", type="integer", example=120),
     *                 @OA\Property(property="published", type="integer", example=85),
     *                 @OA\Property(property="low_stock", type="integer", example=10),
     *                 @OA\Property(property="total_value", type="number", format="float", example=45200.75)
     *             ),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Seller account not set",
     *         @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status", type="boolean", example=false),
     *           @OA\Property(property="message", type="string", example="Seller account not found"),
     *           @OA\Property(property="code", type="integer", example=404),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No active store",
     *         @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status", type="boolean", example=false),
     *           @OA\Property(property="message", type="string", example="Error : no active store"),
     *           @OA\Property(property="code", type="integer", example=400),
     *         )
     *     )
     * )
     */
    public function products()
    {
        $authId = auth()->id();
        $seller = Seller::with('store')->where('user_id', $authId)->first();

        if (!$seller) {
            return ResponseHelper::error([], "Seller account not set", 404);
        }

        $store = $seller->store;
        if (!$store) {
            return ResponseHelper::error([], "Error: No active store.", 400);
        }

        $productsQuery = Product::where('store_id', $seller->active_store);

        // Counts
        $totalProducts = $productsQuery->count();
        $published = (clone $productsQuery)->where('is_online', true)->count();

        $lowStock = (clone $productsQuery)
            ->whereColumn('stock_qty', '<', 'low_stock_threshold')
            ->count();

        $totalValue = (clone $productsQuery)
            ->select(DB::raw('SUM(stock_qty * price) as total'))
            ->value('total') ?? 0;

        $data = [
            'total_products' => $totalProducts,
            'published' => $published,
            'low_stock' => $lowStock,
            'total_value' => $totalValue,
        ];

        return ResponseHelper::success($data, 'Product statistics fetched successfully');
    }





}
