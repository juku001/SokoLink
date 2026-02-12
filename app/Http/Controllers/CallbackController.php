<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Helpers\SMSHelper;
use App\Models\Address;
use App\Models\AirtelCallbackLog;
use App\Models\Escrow;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Region;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallbackController extends Controller
{

    public function airtel(Request $request)
    {

        $transaction = $request->transaction;
        $referenceId = $transaction['id'] ?? null;
        $message = $transaction['message'] ?? '';
        $txnId = $transaction['airtel_money_id'] ?? null;
        $statusCode = $transaction['status_code'] ?? null;

        if (empty($referenceId)) {
            AirtelCallbackLog::create([
                'payload' => json_encode($transaction),
                'airtel_money_id' => $txnId,
                'result' => 'No reference ID provided',
                'status_code' => $statusCode,
                'status' => 'failed'
            ]);
            return ResponseHelper::error([], 'No reference ID provided.', 400);
        }

        $payment = Payment::where('reference', $referenceId)->first();

        if (!$payment) {
            AirtelCallbackLog::create([
                'payload' => json_encode($transaction),
                'airtel_money_id' => $txnId,
                'reference' => $referenceId,
                'result' => 'Payment not found',
                'status_code' => $statusCode,
                'status' => 'failed'
            ]);
            return ResponseHelper::error([], 'Payment not found.', 404);
        }

        if ($payment->status !== 'pending') {
            AirtelCallbackLog::create([
                'payload' => json_encode($transaction),
                'airtel_money_id' => $txnId,
                'reference' => $referenceId,
                'result' => 'Payment already processed',
                'status_code' => $statusCode,
                'status' => 'failed'
            ]);
            return ResponseHelper::error([], 'Payment already processed.', 409);
        }

        try {
            DB::beginTransaction();

            $payment->status = 'successful';
            $payment->save();

            // Get cart from payment
            $cart = $payment->cart;
            if (!$cart) {
                return ResponseHelper::error([], 'Cart not found for this payment.', 404);
            }

            // Get checkout data from session
            $checkoutData = session('checkout_data_' . $cart->id);
            if (!$checkoutData) {
                return ResponseHelper::error([], 'Checkout data not found.', 404);
            }

            // Create order after successful payment
            $order = Order::create([
                'cart_id' => $cart->id,
                'buyer_id' => $payment->user_id,
                'total_amount' => $checkoutData['total_amount'],
                'shipping_cost' => $checkoutData['shipping_cost'],
                'status' => 'paid',
                'payment_option_id' => $checkoutData['payment_option_id'],
                'payment_method_id' => $checkoutData['payment_method_id']
            ]);

            // Create address
            $region = Region::findOrFail($checkoutData['region_id']);
            Address::create([
                'user_id' => $payment->user_id,
                'order_id' => $order->id,
                'type' => 'shipping',
                'fullname' => $checkoutData['fullname'],
                'street' => $checkoutData['address'],
                'region_id' => $region->id,
                'country_id' => $region->country_id,
                'phone' => $checkoutData['address_phone'] ?? $checkoutData['phone'],
            ]);

            // Create order items from cart items
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ]);
            }

            // Link payment to order
            $payment->order_id = $order->id;
            $payment->save();

            AirtelCallbackLog::create([
                'payload' => json_encode($transaction),
                'airtel_money_id' => $txnId,
                'reference' => $referenceId,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'message' => $message,
                'result' => 'Payment made successful',
                'status_code' => $statusCode,
                'status' => 'success'
            ]);

            // Delete cart after successful payment
            $cart->delete();

            // Clear checkout data from session
            session()->forget('checkout_data_' . $cart->id);

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

                $seller = User::find($sellerId);

                $SellerMessage = "Hello SokoLink Merchant, you have a new payment of " . $total . " with an order " . $order->order_ref;
                SMSHelper::send($seller->phone, $SellerMessage);

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

            $buyer = $payment->user;
            $buyerMessage = "Hello Dear Customer, you payment with order " . $order->order_ref . " was successful.";
            SMSHelper::send($buyer->phone, $buyerMessage);

            DB::commit();

            return ResponseHelper::success([], 'Payment confirmed, order created and cart deleted.');

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }



}
