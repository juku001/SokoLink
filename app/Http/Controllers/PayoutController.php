<?php

namespace App\Http\Controllers;

use App\Helpers\PayoutHelper;
use App\Helpers\ResponseHelper;
use App\Models\EscrowBalance;
use App\Models\Payout;
use App\Models\Seller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class PayoutController extends Controller
{
    /**
     * @OA\Get(
     *     path="/payouts",
     *     summary="List seller's payouts",
     *     description="Returns all payouts that belong to the authenticated seller.",
     *     tags={"Payouts"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of payouts",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of payouts"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=12),
     *                     @OA\Property(property="amount", type="number", format="float", example=500.00),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
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
     *       description="Forbidden",
     *       ref="#/components/responses/403"
     *     )
     * )
     */
    public function index()
    {
        $authId = auth()->id();

        $payouts = Payout::with('user')
            ->where('seller_id', $authId)
            ->get();

        return ResponseHelper::success($payouts, 'List of payouts');
    }

    /**
     * @OA\Post(
     *     path="/payouts",
     *     summary="Request a payout",
     *     description="Creates a payout request for the authenticated seller and sends the disbursement to Airtel. 
     *                  On success, the payout is recorded and the seller’s escrow balance is reduced.",
     *     operationId="createPayout",
     *     tags={"Payouts"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", format="float", example=25000, description="Amount to withdraw (TZS). Must be ≥ 1."),
     *             @OA\Property(property="note", type="string", maxLength=255, example="Weekly withdrawal", description="Optional note for the payout.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payout processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payout processed successfully."),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="transaction_id", type="string", example="18****354", description="Airtel reference/transaction ID.")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Insufficient balance or seller account missing",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Insufficient balance"),
     *             @OA\Property(property="code", type="integer", example=400),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=502,
     *         description="Payout failed on Airtel side",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payout failed: Airtel error message"),
     *             @OA\Property(property="code", type="integer", example=502),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error: database or processing failure"),
     *             @OA\Property(property="code", type="integer", example=500),
     *         )
     *     )
     * )
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Validation failed', 422);
        }
        $sellerId = auth()->user()->id;

        $seller = Seller::where('user_id', $sellerId)->first();
        if (!$seller) {
            return ResponseHelper::error([], "Seller account not found.", 404);
        }

        $escrowBalance = EscrowBalance::where('user_id', $sellerId)->first();
        if ($escrowBalance->balance <= $request->amount) {
            return ResponseHelper::error([], 'Insufficient balance', 400);
        }

        DB::beginTransaction();
        try {

            $payoutHelper = new PayoutHelper($sellerId, $request->amount);
            $response = $payoutHelper->initiatePayout();

            $payoutData = [
                'seller_id' => $sellerId,
                'amount' => $request->amount,
                'ref_id' => $response['reference'],
                'txn_id' => $response['transaction_id'] ?? null,
                'gateway_message' => $response['message'],
                'payment_method_id' => $seller->payment_method_id,
                'payment_account' => $seller->payout_account,
                'paid_at' => now()
            ];

            if ($response['status'] === false) {
                // ---- FAIL ----
                $payoutData['status'] = 'failed';
                Payout::create($payoutData);

                DB::commit();
                return ResponseHelper::error([], 'Payout failed: ' . $response['message'], 502);
            }

            // ---- SUCCESS ----
            $payoutData['status'] = 'completed';
            Payout::create($payoutData);

            $escrowBalance = EscrowBalance::lockForUpdate()
                ->where('user_id', $sellerId)
                ->first();

            if (!$escrowBalance || $escrowBalance->balance < $request->amount) {
                throw new Exception('Insufficient escrow balance.');
            }

            $escrowBalance->balance -= $request->amount;
            $escrowBalance->save();

            DB::commit();
            return ResponseHelper::success(
                ['transaction_id' => $response['transaction_id']],
                'Payout processed successfully.'
            );

        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/payouts/{id}",
     *     summary="Get payout details",
     *     description="Returns details of a specific payout if it belongs to the authenticated seller.",
     *     tags={"Payouts"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payout ID",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payout details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payout details"),
     *             @OA\Property(property="data",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(property="amount", type="number", example=500),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="payment_method_id", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *       response=404, 
     *     description="Payout not found",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="status", type="boolean", example=false),
     *        @OA\Property(property="message", type="string", example="Payout not found."),
     *        @OA\Property(property="code", type="integer", example=404),
     *      )
     *   )
     * )
     */
    public function show($id)
    {
        $authId = auth()->id();

        $payout = Payout::with(['user', 'paymentMethod'])
            ->where('seller_id', $authId)
            ->find($id);

        if (!$payout) {
            return ResponseHelper::error([], 'Payout not found', 404);
        }

        return ResponseHelper::success($payout, 'Payout details');
    }

    /**
     * @OA\Get(
     *     path="/payouts/all",
     *     summary="List all payouts (Admin only)",
     *     description="Accessible only by super admins to list every payout.",
     *     tags={"Payouts"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of all payouts",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of all payouts"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=20),
     *                     @OA\Property(property="seller_id", type="integer", example=4),
     *                     @OA\Property(property="amount", type="number", example=750.00),
     *                     @OA\Property(property="status", type="string", example="completed")
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
     *       description="Forbidden",
     *       ref="#/components/responses/403"
     *     )
     * )
     */
    public function all()
    {
        $payouts = Payout::with('user', 'paymentMethod')->get();

        return ResponseHelper::success($payouts, 'List of all payouts');
    }





    /**
     * @OA\Patch(
     *     path="/payout/settlement-type",
     *     summary="Toggle seller settlement type (manual/auto)",
     *     tags={"Payouts"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Settlement type changed successfully",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="status", type="boolean", example=true),
     *        @OA\Property(property="message", type="string", example="Settlement type changed.."),
     *        @OA\Property(property="code", type="integer", example=200),
     *      )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Seller record not found",
     *         @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="status", type="boolean", example=false),
     *        @OA\Property(property="message", type="string", example="Seller record not found."),
     *        @OA\Property(property="code", type="integer", example=404),
     *      )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */
    public function type()
    {
        try {
            $authId = auth()->id();

            $seller = Seller::where('user_id', $authId)->first();
            if (!$seller) {
                return ResponseHelper::error([], 'Seller record not found.', 404);
            }

            $seller->settlement = $seller->settlement === 'manual' ? 'auto' : 'manual';
            $seller->save();

            return ResponseHelper::success(
                ['settlement' => $seller->settlement],
                'Settlement type changed.'
            );

        } catch (\Exception $e) {
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }

}
