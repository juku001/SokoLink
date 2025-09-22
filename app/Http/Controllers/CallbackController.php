<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Address;
use App\Models\Escrow;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallbackController extends Controller
{

    public function airtel(Request $request)
    {
        $referenceId = $request->input("ref");

        if (empty($referenceId)) {
            return ResponseHelper::error([], 'No reference ID provided.', 400);
        }

        $payment = Payment::where('reference', $referenceId)->first();

        if (!$payment) {
            return ResponseHelper::error([], 'Payment not found.', 404);
        }

        if ($payment->status !== 'pending') {
            return ResponseHelper::error([], 'Payment already processed.', 409);
        }

        try {
            DB::beginTransaction();

            $payment->status = 'successful';
            $payment->save();

            $order = $payment->order;
            if (!$order) {
                return ResponseHelper::error([], 'Order not found for this payment.', 404);
            }

            $order->status = 'paid';
            $order->save();

            $order->load('items.product.store', 'address');

            $itemsBySeller = [];
            foreach ($order->items as $item) {
                $sellerId = $item->product->store->seller_id;
                if (!isset($itemsBySeller[$sellerId])) {
                    $itemsBySeller[$sellerId] = [
                        'total' => 0,
                        'items' => []
                    ];
                }
                $lineTotal = $item->quantity * $item->price;
                $itemsBySeller[$sellerId]['total'] += $lineTotal;
                $itemsBySeller[$sellerId]['items'][] = $item;
            }

            foreach ($itemsBySeller as $sellerId => $sellerData) {
                $total = $sellerData['total'];
                $platformFee = round($total * 0.10, 2);
                $sellerAmount = $total - $platformFee;

                $escrow = Escrow::create([
                    'order_id' => $order->id,
                    'buyer_id' => $order->buyer_id,
                    'seller_id' => $sellerId,
                    'total_amount' => $total,
                    'seller_amount' => $sellerAmount,
                    'platform_fee' => $platformFee,
                    'payment_id' => $payment->id,
                    'status' => 'holding',
                ]);

                $sale = Sale::create([
                    'seller_id' => $sellerId,
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'payment_id' => $payment->id,
                    'payment_method_id' => $payment->payment_method_id,
                    'payment_type' => 'mno',
                    'buyer_name' => $order->address->fullname,
                    'amount' => $order->total_amount,
                    'sale_date' => now()->toDateString(),
                    'sale_time' => now()->toTimeString(),
                    'status' => 'completed',
                ]);

                foreach ($sellerData['items'] as $item) {
                    SaleProduct::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total' => $item['quantity'] * $item['price'],
                    ]);
                }


                if ($order->shipping_cost > 0) {
                    Shipment::create([
                        'order_id' => $order->id,
                        'seller_id' => $sellerId,
                        'address_id' => $order->address->id,
                        'status' => 'pending',
                    ]);
                }
            }


            DB::commit();

            return ResponseHelper::success([], 'Payment confirmed, escrows and shipments created.');

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }



}
