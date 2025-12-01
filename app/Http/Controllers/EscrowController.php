<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Escrow;
use App\Models\EscrowBalance;
use App\Models\Store;
use Illuminate\Http\Request;

class EscrowController extends Controller
{
    /**
     * @OA\Get(
     *     path="/escrow/balance/stores/{id}",
     *     summary="Get escrow balance for a specific store",
     *     description="Returns the escrow balance of a store owned by the authenticated seller.",
     *     tags={"Payouts"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Store ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Store Balance retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Store Balance"),
     *              @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="number", format="float", example=1200.50)
     *         )
     *     ),
     *     @OA\Response(
     *       response=404, 
     *       description="Store not found",
     *       @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Store Not Found"),
     *              @OA\Property(property="code", type="integer", example=404),
     *         )
     *     ),
     *     @OA\Response(
     *       response=400, 
     *       description="Store is not on your ownership",
     *        @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Store Not Found"),
     *              @OA\Property(property="code", type="integer", example=400),
     *         )
     *     ),
     *      @OA\Response(
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
    public function storesBalance(string $storeId)
    {
        $store = Store::find($storeId);
        if (!$store) {
            return ResponseHelper::error([], "Store not found", 404);
        }

        $authId = auth()->user()->id;
        if ($store->seller_id !== $authId) {
            return ResponseHelper::error([], 'Store is not on your ownership.', 400);
        }

        $escrowBalance = EscrowBalance::where('store_id', $storeId)
            ->where('user_id', $authId)
            ->first();

        return ResponseHelper::success($escrowBalance->balance ?? 0, 'Store Balance');
    }

    /**
     * @OA\Get(
     *     path="/escrow/balance/merchant",
     *     summary="Get total escrow balance for authenticated merchant",
     *     description="Sums all escrow balances across the merchant's stores.",
     *     tags={"Payouts"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Total merchant balance retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant Balance"),
     *             @OA\Property(property="code", type="integer", example=200), 
     *             @OA\Property(property="data", type="number", format="float", example=3200.75)
     *         )
     *     ),
     *      @OA\Response(
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
    public function merchantBalance()
    {
        $authId = auth()->user()->id;
        $escrowBalance = EscrowBalance::where('user_id', $authId)->get();

        $totalBalance = $escrowBalance->sum('balance');

        return ResponseHelper::success($totalBalance, 'Merchant Balance');
    }

    /**
     * @OA\Get(
     *     path="/escrows",
     *     summary="List all escrows for authenticated seller",
     *     description="Returns every escrow record where the current user is the seller.",
     *     tags={"Payouts"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of escrows",
     *                 @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of all escrows"),
     *             @OA\Property(property="code", type="integer", example=200),
     * 
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=45),
     *                     @OA\Property(property="order_id", type="integer", example=102),
     *                     @OA\Property(property="buyer_id", type="integer", example=17),
     *                     @OA\Property(property="seller_id", type="integer", example=5),
     *                     @OA\Property(property="status", type="string", example="holding"),
     *                     @OA\Property(property="total_amount", type="number", format="float", example=250.00),
     *                     @OA\Property(property="seller_amount", type="number", format="float", example=225.00),
     *                     @OA\Property(property="platform_fee", type="number", format="float", example=25.00),
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
        $authId = auth()->user()->id;
        $escrows = Escrow::with('buyer')
            ->where('seller_id', $authId)
            ->get();

        return ResponseHelper::success($escrows, 'List of all escrows');
    }
}
