<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Payment;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{


    /**
     * @OA\Get(
     *     path="/admin/payments",
     *     summary="Get list of payments",
     *     description="Returns a paginated list of payments with optional filters: search, payment_method, merchant, status",
     *     tags={"Admin"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by transaction ID or reference",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="payment_method",
     *         in="query",
     *         description="Filter by payment method display name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="merchant",
     *         in="query",
     *         description="Filter by merchant/store name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by payment status (e.g., success, failed)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of payments retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of all payments"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="ref", type="string", example="REF12345"),
     *                         @OA\Property(property="txn_id", type="string", example="TXN98765"),
     *                         @OA\Property(property="amount", type="number", format="float", example=2500),
     *                         @OA\Property(property="merchant", type="string", example="Best Store"),
     *                         @OA\Property(property="method", type="string", example="PayPal"),
     *                         @OA\Property(property="status", type="string", example="success"),
     *                         @OA\Property(property="time", type="string", format="date-time", example="2025-09-16T12:34:56")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="pagination",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="last_page", type="integer", example=10),
     *                     @OA\Property(property="per_page", type="integer", example=50),
     *                     @OA\Property(property="total", type="integer", example=500)
     *                 )
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function index(Request $request)
    {
        $query = Payment::with(['store', 'paymentMethod']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        if ($request->filled('payment_method')) {
            $query->whereHas('paymentMethod', function ($q) use ($request) {
                $q->where('display', $request->payment_method);
            });
        }

        if ($request->filled('merchant')) {
            $query->whereHas('store', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->merchant}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->orderBy('updated_at', 'desc')->paginate(50);

        $data = $payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'ref' => $payment->reference,
                'txn_id' => $payment->transaction_id,
                'amount' => $payment->amount,
                'merchant' => $payment->store?->name,
                'method' => $payment->paymentMethod?->display,
                'status' => $payment->status,
                'time' => $payment->updated_at,
            ];
        });

        return ResponseHelper::success([
            'data' => $data,
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ]
        ], 'List of all payments');
    }
    /**
     * @OA\Get(
     *     path="/admin/payments/{id}",
     *     summary="Get payment details",
     *     description="Retrieve details of a single payment including store, user, order, payment method, and payment option",
     *     tags={"Admin"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the payment",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment details"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="amount", type="number", format="float", example=2500),
     *                 @OA\Property(property="status", type="string", example="success"),
     *                 @OA\Property(property="ref", type="string", example="REF12345"),
     *                 @OA\Property(property="txn_id", type="string", example="TXN98765"),
     *                 @OA\Property(property="store", type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="name", type="string", example="Best Store")
     *                 ),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="John Doe")
     *                 ),
     *                 @OA\Property(property="order", type="object",
     *                     @OA\Property(property="id", type="integer", example=1001),
     *                     @OA\Property(property="status", type="string", example="completed")
     *                 ),
     *                 @OA\Property(property="paymentMethod", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="display", type="string", example="PayPal")
     *                 ),
     *                 @OA\Property(property="paymentOption", type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="display", type="string", example="Credit Card")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payment not found."),
     *             )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function show($id)
    {

        $payment = Payment::with(
            [
                'store',
                'user',
                'order',
                'paymentMethod',
                'paymentOption'
            ]
        )->find($id);

        if (!$payment) {
            return ResponseHelper::error([], 'Payment not found.', 404);
        }

        return ResponseHelper::success($payment, 'Payment details');


    }
}
