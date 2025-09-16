<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Order;
use App\Models\Shipment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DeliveryController extends Controller
{


    /**
     * @OA\Get(
     *     path="/orders/shipping",
     *     summary="Get list of shipment orders for the authenticated seller",
     *     description="Retrieve all shipments associated with the authenticated seller along with related order information.",
     *     operationId="listShipments",
     *     tags={"Orders"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of shipment orders",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of shipment order for the sellers."),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="order_id", type="integer", example=101),
     *                     @OA\Property(property="seller_id", type="integer", example=10),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="delivered_late", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-16T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-16T12:00:00Z"),
     *                     @OA\Property(
     *                         property="order",
     *                         type="object",
     *                         description="Related order details"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/401"
     *     )
     * )
     */
    public function index()
    {
        $authId = auth()->user()->id;
        $shipment = Shipment::with('order')->where("seller_id", $authId)->get();
        return ResponseHelper::success($shipment, 'List of shipment order for the sellers.');
    }







    /**
     * @OA\Put(
     *     path="/orders/shipping/{id}",
     *     summary="Update a shipment and mark order as shipped",
     *     description="Update carrier information for a shipment and change the order status to shipped. Only accessible by the seller who owns the shipment.",
     *     operationId="updateShipment",
     *     tags={"Orders"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Shipment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="carrier", type="string", example="DHL"),
     *             @OA\Property(property="carrier_mobile", type="string", example="+255712345678")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shipment updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products shipped to buyer."),
     *             @OA\Property(property="code", type="integer", example=200),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request (e.g., order not paid or already shipped)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order is not yet paid for."),
     *             @OA\Property(property="code", type="integer", example=400),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shipment not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Shipment not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $authId = auth()->user()->id;
        $shipment = Shipment::where('id', $id)->where('seller_id', $authId)->first();
        if (!$shipment) {
            return ResponseHelper::error([], 'Shipment not found');
        }

        $validator = Validator::make($request->all(), [
            'carrier' => 'required|string',
            'carrier_mobile' => 'required|string|regex:/^\+255\d{9}$/'
        ], [
            'carrier_mobile.regex' => 'Mobile number should be +255XXXXXXXXX'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields',
                422
            );
        }

        DB::beginTransaction();
        try {
            $order = Order::find($shipment->order_id);

            if ($order->status === 'pending') {
                return ResponseHelper::error([], 'Order is not yet paid for.', 400);
            }
            if (in_array($order->status, ['shipped', 'delivered'])) {
                return ResponseHelper::error([], 'This order cannot be shipped again.', 400);
            }

            $order->status = 'shipped';
            $order->save();

            $shipment->carrier = $request->carrier;
            $shipment->carrier_mobile = $request->carrier_mobile;
            $shipment->status = 'shipped';
            $shipment->shipped_at = now();
            $shipment->save();

            DB::commit();
            return ResponseHelper::success([], 'Products shipped to buyer.');

        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }



    public function delivered(Request $request)
    {

    }



}
