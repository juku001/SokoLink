<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Escrow;
use App\Models\EscrowBalance;
use App\Models\Order;
use App\Models\Shipment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DeliveryController extends Controller
{


    // /**
    //  * @OA\Get(
    //  *     path="/orders/shipping",
    //  *     summary="Get list of shipment orders for the authenticated seller",
    //  *     description="Retrieve all shipments associated with the authenticated seller along with related order information.",
    //  *     operationId="listShipments",
    //  *     tags={"Orders"},
    //  *     security={{"bearerAuth": {}}},
    //  *
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="List of shipment orders",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="boolean", example=true),
    //  *             @OA\Property(property="message", type="string", example="List of shipment order for the sellers."),
    //  *             @OA\Property(property="code", type="integer", example=200),
    //  *             @OA\Property(
    //  *                 property="data",
    //  *                 type="array",
    //  *                 @OA\Items(
    //  *                     type="object",
    //  *                     @OA\Property(property="id", type="integer", example=1),
    //  *                     @OA\Property(property="order_id", type="integer", example=101),
    //  *                     @OA\Property(property="seller_id", type="integer", example=10),
    //  *                     @OA\Property(property="status", type="string", example="pending"),
    //  *                     @OA\Property(property="delivered_late", type="boolean", example=false),
    //  *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-16T12:00:00Z"),
    //  *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-16T12:00:00Z"),
    //  *                     @OA\Property(
    //  *                         property="order",
    //  *                         type="object",
    //  *                         description="Related order details"
    //  *                     )
    //  *                 )
    //  *             )
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=401,
    //  *         description="Unauthorized",
    //  *         ref="#/components/responses/401"
    //  *     )
    //  * )
    //  */
    // public function index()
    // {
    //     $authId = auth()->user()->id;
    //     $shipment = Shipment::with('order')->where("seller_id", $authId)->get();
    //     return ResponseHelper::success($shipment, 'List of shipment order for the sellers.');
    // }







    // /**
    //  * @OA\Put(
    //  *     path="/orders/shipping/{id}",
    //  *     summary="Update a shipment and mark order as shipped",
    //  *     description="Update carrier information for a shipment and change the order status to shipped. Only accessible by the seller who owns the shipment.",
    //  *     operationId="updateShipment",
    //  *     tags={"Orders"},
    //  *     security={{"bearerAuth": {}}},
    //  *     @OA\Parameter(
    //  *         name="id",
    //  *         in="path",
    //  *         description="Shipment ID",
    //  *         required=true,
    //  *         @OA\Schema(type="integer", example=1)
    //  *     ),
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="carrier", type="string", example="DHL"),
    //  *             @OA\Property(property="carrier_mobile", type="string", example="+255712345678")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Shipment updated successfully",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="boolean", example=true),
    //  *             @OA\Property(property="message", type="string", example="Products shipped to buyer."),
    //  *             @OA\Property(property="code", type="integer", example=200),
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=400,
    //  *         description="Bad request (e.g., order not paid or already shipped)",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="boolean", example=false),
    //  *             @OA\Property(property="message", type="string", example="Order is not yet paid for."),
    //  *             @OA\Property(property="code", type="integer", example=400),
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=404,
    //  *         description="Shipment not found",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="boolean", example=false),
    //  *             @OA\Property(property="message", type="string", example="Shipment not found"),
    //  *             @OA\Property(property="code", type="integer", example=404),
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=422,
    //  *         description="Validation failed",
    //  *         ref="#/components/responses/422"
    //  *     ),
    //  *     @OA\Response(
    //  *         response=500,
    //  *         description="Server error",
    //  *         ref="#/components/responses/500"
    //  *     )
    //  * )
    //  */
    // public function update(Request $request, string $id)
    // {
    //     $authId = auth()->user()->id;
    //     $shipment = Shipment::where('id', $id)->where('seller_id', $authId)->first();
    //     if (!$shipment) {
    //         return ResponseHelper::error([], 'Shipment not found');
    //     }

    //     $validator = Validator::make($request->all(), [
    //         'carrier' => 'required|string',
    //         'carrier_mobile' => 'required|string|regex:/^\+255\d{9}$/'
    //     ], [
    //         'carrier_mobile.regex' => 'Mobile number should be +255XXXXXXXXX'
    //     ]);

    //     if ($validator->fails()) {
    //         return ResponseHelper::error(
    //             $validator->errors(),
    //             'Failed to validate fields',
    //             422
    //         );
    //     }

    //     DB::beginTransaction();
    //     try {
    //         $order = Order::find($shipment->order_id);

    //         if ($order->status === 'pending') {
    //             return ResponseHelper::error([], 'Order is not yet paid for.', 400);
    //         }
    //         if (in_array($order->status, ['shipped', 'delivered'])) {
    //             return ResponseHelper::error([], 'This order cannot be shipped again.', 400);
    //         }

    //         $order->status = 'shipped';
    //         $order->save();

    //         $shipment->carrier = $request->carrier;
    //         $shipment->carrier_mobile = $request->carrier_mobile;
    //         $shipment->status = 'shipped';
    //         $shipment->shipped_at = now();
    //         $shipment->save();

    //         DB::commit();
    //         return ResponseHelper::success([], 'Products shipped to buyer.');

    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
    //     }
    // }




    /**
     * @OA\Put(
     *     path="/orders/{id}/delivered",
     *     summary="Mark a product in an order as delivered",
     *     description="Buyer marks a specific product within an order as delivered. 
     *                  If all products for the same seller are delivered, the seller's escrow is released. 
     *                  If all products in the order are delivered, the order status is updated to 'delivered'.",
     *     tags={"Orders"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id"},
     *             @OA\Property(
     *                 property="product_id",
     *                 type="integer",
     *                 example=456,
     *                 description="ID of the product to mark as delivered"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product marked as delivered successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product marked as delivered and escrow updated if applicable."),
     *             @OA\Property(property="code", type="integer", example=200),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad request (e.g. product already delivered or order not paid)",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order is not yet paid."),
     *             @OA\Property(property="code", type="integer", example=400),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Order or product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found."),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to validate fields."),
     *             @OA\Property(property="code", type="integer", example=422),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="product_id", type="array", @OA\Items(type="string", example="The product id field is required."))
     *             )
     *         )
     *     )
     * )
     */

    public function delivered(Request $request, int $orderId)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|numeric|exists:products,id',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields.', 422);
        }

        $authId = auth()->id();
        $order = Order::with('items.product.store')
            ->where('id', $orderId)
            ->where('buyer_id', $authId)
            ->first();

        if (!$order) {
            return ResponseHelper::error([], "Order not found.", 404);
        }

        if ($order->status !== 'paid') {
            return ResponseHelper::error([], 'Order is not yet paid.', 400);
        }

        DB::beginTransaction();
        try {
            $item = $order->items
                ->where('product_id', $request->product_id)
                ->first();

            if (!$item) {
                DB::rollBack();
                return ResponseHelper::error([], 'Product is not part of this order.', 404);
            }

            if ($item->delivered) {
                DB::rollBack();
                return ResponseHelper::error([], 'This product is already marked as delivered.', 400);
            }

            $item->delivered = true;
            $item->save();

            $sellerId = $item->product->store->seller_id;
            $storeId = $item->product->store->id;
            $undelivered = $order->items
                ->where('product.store.seller_id', $sellerId)
                ->where('delivered', false)
                ->count();

            if ($undelivered === 0) {

                $escrow = Escrow::where('order_id', $order->id)
                    ->where('seller_id', $sellerId)
                    ->where('buyer_id', $authId)
                    ->where('status', 'holding')
                    ->first();

                if ($escrow) {
                    $escrow->status = 'released';
                    $escrow->released_at = now();
                    $escrow->save();

                    $escrowBalance = EscrowBalance::where('user_id', $sellerId)
                        ->where('store_id', $storeId)
                        ->first();

                    if ($escrowBalance) {

                        $escrowBalance->balance += $escrow->seller_amount;
                        $escrowBalance->save();
                    } else {

                        EscrowBalance::create([
                            'user_id' => $sellerId,
                            'store_id' => $storeId,
                            'balance' => $escrow->seller_amount,
                        ]);
                    }

                }
            }

            $remaining = $order->items->where('delivered', false)->count();
            if ($remaining === 0) {
                $order->status = 'delivered';
                $order->delivered_at = now();
                $order->save();
            }
            DB::commit();
            return ResponseHelper::success([], 'Product marked as delivered and escrow updated if applicable.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }




}
