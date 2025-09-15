<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Address;
use App\Models\Escrow;
use App\Models\Payment;
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
                $platformFee = round($total * 0.10, 2); // 10% fee
                $sellerAmount = $total - $platformFee;

                Escrow::create([
                    'order_id' => $order->id,
                    'buyer_id' => $order->buyer_id,
                    'seller_id' => $sellerId,
                    'total_amount' => $total,
                    'seller_amount' => $sellerAmount,
                    'platform_fee' => $platformFee,
                    'payment_id' => $payment->id,
                    'status' => 'holding',
                ]);

                $shipment = new Shipment();
                $shipment->order_id = $order->id;
                $shipment->seller_id = $sellerId; // new field in shipments table
                $shipment->address_id = $order->address->id;
                $shipment->status = 'pending';
                $shipment->save();
            }

            DB::commit();

            return ResponseHelper::success([], 'Payment confirmed, escrows and shipments created.');

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }



}
