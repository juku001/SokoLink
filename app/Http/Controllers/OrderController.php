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
    public function index()
    {
        $authId = auth()->id();

        $orders = Order::with(['items.product.store'])->where('buyer_id', $authId)->latest()->get();

        return ResponseHelper::success($orders, "List of orders");
    }


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
