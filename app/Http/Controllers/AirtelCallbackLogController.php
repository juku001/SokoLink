<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\AirtelCallbackLog;
use Illuminate\Http\Request;

class AirtelCallbackLogController extends Controller
{
    /**
     * @OA\Get(
     *     path="/admin/airtel/callback/logs",
     *     tags={"Admin"},
     *     summary="List and filter Airtel callback logs",
     *     description="Retrieve Airtel callback logs with optional search and filtering options.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reference",
     *         in="query",
     *         description="Search by internal reference",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="airtel_money_id",
     *         in="query",
     *         description="Search by Airtel Money transaction ID",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="min_amount",
     *         in="query",
     *         description="Filter by minimum amount",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="max_amount",
     *         in="query",
     *         description="Filter by maximum amount",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (success or failed)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"success","failed"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of all airtel callback logs"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/AirtelCallbackLog")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", ref="#/components/responses/401"),
     *     @OA\Response(response=403, description="Forbidden", ref="#/components/responses/403")
     * )
     */
    public function __invoke(Request $request)
    {
        $query = AirtelCallbackLog::with('payment');

        // --- Optional filters ---
        if ($ref = $request->query('reference')) {
            $query->where('reference', 'like', "%{$ref}%");
        }

        if ($airtelId = $request->query('airtel_money_id')) {
            $query->where('airtel_money_id', 'like', "%{$airtelId}%");
        }

        if ($min = $request->query('min_amount')) {
            $query->where('amount', '>=', $min);
        }

        if ($max = $request->query('max_amount')) {
            $query->where('amount', '<=', $max);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $callbackLogs = $query->latest()->paginate(100);

        return ResponseHelper::success($callbackLogs, "List of all airtel callback logs");
    }
}
