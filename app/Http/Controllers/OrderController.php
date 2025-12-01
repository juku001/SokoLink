<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use DB;
use Exception;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/orders",
     *     summary="Get list of buyer's orders",
     *     description="Returns all orders for the authenticated buyer along with items, product, and store details.",
     *     operationId="getOrders",
     *     tags={"Orders"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of orders retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of orders"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=101),
     *                     @OA\Property(property="buyer_id", type="integer", example=10),
     *                     @OA\Property(property="total_amount", type="number", format="float", example=50000),
     *                     @OA\Property(property="shipping_cost", type="number", format="float", example=5000),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="payment_option_id", type="integer", example=1),
     *                     @OA\Property(property="payment_method_id", type="integer", example=2),
     *                     @OA\Property(
     *                         property="items",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="product_id", type="integer", example=10),
     *                             @OA\Property(property="quantity", type="integer", example=2),
     *                             @OA\Property(property="price", type="number", format="float", example=15000),
     *                             @OA\Property(
     *                                 property="product",
     *                                 type="object",
     *                                 @OA\Property(property="name", type="string", example="Smartphone Case"),
     *                                 @OA\Property(
     *                                     property="store",
     *                                     type="object",
     *                                     @OA\Property(property="name", type="string", example="Tech Store")
     *                                 )
     *                             )
     *                         )
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
     *     )
     * )
     */
    public function index()
    {
        $authId = auth()->id();

        $orders = Order::with(['items.product.store'])->where('buyer_id', $authId)->latest()->get();

        return ResponseHelper::success($orders, "List of orders");
    }



    /**
     * @OA\Get(
     *     path="/orders/{id}",
     *     summary="Get details of a specific order",
     *     description="Returns details of a specific order for the authenticated buyer including items, products, and store details.",
     *     operationId="getOrderById",
     *     tags={"Orders"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the order",
     *         required=true,
     *         @OA\Schema(type="integer", example=101)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Order details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order details"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=101),
     *                 @OA\Property(property="buyer_id", type="integer", example=10),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=50000),
     *                 @OA\Property(property="shipping_cost", type="number", format="float", example=5000),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="payment_option_id", type="integer", example=1),
     *                 @OA\Property(property="payment_method_id", type="integer", example=2),
     *                 @OA\Property(
     *                     property="items",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="product_id", type="integer", example=10),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="price", type="number", format="float", example=15000),
     *                         @OA\Property(
     *                             property="product",
     *                             type="object",
     *                             @OA\Property(property="name", type="string", example="Smartphone Case"),
     *                             @OA\Property(
     *                                 property="store",
     *                                 type="object",
     *                                 @OA\Property(property="name", type="string", example="Tech Store")
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         ref="#/components/responses/401"
     *     )
     * )
     */


    public function show(string $id)
    {
        $authId = auth()->id();

        $order = Order::with(['items.product.store'])
            ->where('buyer_id', $authId)
            ->find($id);

        if (!$order) {
            return ResponseHelper::error([], "Order not found", 404);
        }

        return ResponseHelper::success($order, "Order details");
    }



    /**
     * @OA\Post(
     *     path="/orders/cancel/{id}",
     *     summary="Cancel an order",
     *     description="Cancel a specific order for the authenticated buyer if it is not yet shipped, delivered, or already cancelled.",
     *     operationId="cancelOrder",
     *     tags={"Orders"},
     *     security={{"bearerAuth": {}}},
     * 
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the order to cancel",
     *         required=true,
     *         @OA\Schema(type="integer", example=101)
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="Order cancelled successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order cancelled successfully"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=101),
     *                 @OA\Property(property="buyer_id", type="integer", example=10),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=50000),
     *                 @OA\Property(property="shipping_cost", type="number", format="float", example=5000),
     *                 @OA\Property(property="status", type="string", example="cancelled"),
     *                 @OA\Property(property="payment_option_id", type="integer", example=1),
     *                 @OA\Property(property="payment_method_id", type="integer", example=2),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-16T09:30:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-16T10:00:00Z")
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=400,
     *         description="Order cannot be cancelled at this stage",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order cannot be cancelled at this stage")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */
    public function cancel(string $id)
    {
        $authId = auth()->id();

        $order = Order::where('buyer_id', $authId)->find($id);

        if (!$order) {
            return ResponseHelper::error([], "Order not found", 404);
        }

        if (in_array($order->status, ['shipped', 'delivered', 'cancelled'])) {
            return ResponseHelper::error([], "Order cannot be cancelled at this stage", 400);
        }

        DB::beginTransaction();
        try {
            $order->update(['status' => 'cancelled']);

            $order->statusHistories()->create([
                'status' => 'cancelled',
                'note' => 'Order Cancelled'
            ]);
            DB::commit();

            return ResponseHelper::success($order, "Order cancelled successfully");

        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], "Error : " . $e->getMessage());
        }
    }





    public function refund(string $id)
    {
        $authId = auth()->id();

        $order = Order::where('buyer_id', $authId)->find($id);

        if (!$order) {
            return ResponseHelper::error([], "Order not found", 404);
        }

        if ($order->status !== 'delivered') {
            return ResponseHelper::error([], "Refund can only be requested after delivery", 400);
        }

        // record refund request (this could notify admin/seller)
        $order->update(['status' => 'refund_requested']);

        $order->statusHistories()->create([
            'status' => 'refund_requested'
        ]);

        return ResponseHelper::success($order, "Refund requested successfully");
    }


    /**
     * @OA\Get(
     *     path="/orders/status/{id}",
     *     summary="Get order status history",
     *     description="Retrieve the status history of a specific order for the authenticated buyer.",
     *     operationId="getOrderStatusHistory",
     *     tags={"Orders"},
     *     security={{"bearerAuth": {}}},
     * 
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the order",
     *         required=true,
     *         @OA\Schema(type="integer", example=101)
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="Order status history retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of order status history"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="order_id", type="integer", example=101),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="note", type="string", example="Order created"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-14T12:34:56Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-14T12:34:56Z")
     *                 )
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found."),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         ref="#/components/responses/401"
     *     )
     * )
     */
    public function status(string $id)
    {
        $authId = auth()->id();

        $order = Order::where('buyer_id', $authId)->find($id);
        if (!$order) {
            return ResponseHelper::error(
                [],
                "Order not found.",
                404
            );
        }

        $orderHistory = OrderStatusHistory::where('order_id', $order->id)->latest()->get();

        return ResponseHelper::success($orderHistory, 'List of order status history');
    }


}
