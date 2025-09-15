<?php

namespace App\Http\Controllers;

use App\Helpers\MobileNetworkHelper;
use App\Helpers\PaymentHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\SMSHelper;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentOptions;
use App\Models\Region;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Validator;

class PaymentController extends Controller
{


    public function index(Request $request)
    {
        $authId = auth()->user()->id;

        $query = Payment::with(['order', 'paymentMethod'])
            ->where("user_id", $authId);

        // 🔹 Filter by payment method
        if ($request->has('payment_method_id') && !empty($request->payment_method_id)) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        // 🔹 Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // 🔹 Search by order_ref (from orders) or payment reference
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'LIKE', "%{$search}%")
                    ->orWhereHas('order', function ($sub) use ($search) {
                        $sub->where('order_ref', 'LIKE', "%{$search}%");
                    });
            });
        }

        $payments = $query->latest()->paginate(50); // paginated

        return ResponseHelper::success($payments, 'List of payments');
    }


    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string',
            'phone' => 'required|string|regex:/^\+255\d{9}$/',
            'address' => 'required|string',
            'region_id' => 'required|numeric|exists:regions,id',
            'payment_method_id' => 'required|numeric|exists:payment_methods,id',
            'payment_option_id' => 'required|numeric|exists:payment_options,id',
        ], [
            'phone.regex' => 'Mobile phone should be like +255XXXXXXXXX'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields", 422);
        }

        DB::beginTransaction();
        try {
            $authId = auth()->id();

            $cart = Cart::with('items.product.store')
                ->where('buyer_id', $authId)
                ->first();

            if (!$cart || $cart->items->count() == 0) {
                return ResponseHelper::error([], 'Please add items to cart first.', 400);
            }

            $subTotal = $cart->items->sum(fn($i) => $i->price * $i->quantity);
            $shipping = 5000;
            $total = $subTotal + $shipping;


            $order = Order::create([
                'buyer_id' => $authId,
                'total_amount' => $total,
                'shipping_cost' => $shipping,
                'status' => 'pending',
                'payment_option_id' => $request->payment_option_id,
                'payment_method_id' => $request->payment_method_id
            ]);


            $region = Region::findOrFail($request->region_id);

            Address::create([
                'user_id' => $authId,
                'order_id' => $order->id,
                'type' => 'shipping',
                'fullname' => $request->fullname,
                'street' => $request->address,
                'region_id' => $region->id,
                'country_id' => $region->country_id,
                'phone' => $request->phone,
            ]);


            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ]);
            }


            $cart->items()->delete();
            $cart->delete();

            DB::commit();

            return ResponseHelper::success([
                'order_id' => $order->id,
                'total' => $total,
                'subtotal' => $subTotal,
                'shipping' => $shipping,
                'items' => $order->items()->with('product.store')->get(),
            ], "Order placed successfully", 201);

        } catch (QueryException $e) {
            return ResponseHelper::error([], "Error : " . $e->getMessage(), 400);
        } catch (Exception $e) {

            DB::rollBack();
            return ResponseHelper::error([], "Error: " . $e->getMessage(), 500);

        }
    }





    public function initiate(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|numeric|exists:orders,id',
            'payment_option_id' => 'required|numeric|exists:payment_options,id'
        ], [
            'order_id.exists' => 'Order does not exist.'
        ]);
        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields.',
                422
            );
        }
        $order = Order::find($request->order_id);
        if (!$order) {
            return ResponseHelper::error([], "Order not found.", 404);
        }
        $check = $this->canBePaid($order);
        if ($check !== true) {
            return $check;
        }
        $checkOptionAndMethods = $this->optionsAndMethods($request);
        if ($checkOptionAndMethods !== true) {
            return $checkOptionAndMethods;
        }
        try {
            $payOption = PaymentOptions::find($request->payment_option_id);
            switch ($payOption->key) {
                case 'pay-now':
                    return $this->handlePayNow($order, $request);
                case 'save-pay-later':
                    return $this->handleSavePayLater($order, $request);
                case 'request-payment':
                    return $this->handleRequestPayment($order, $request);
                default:
                    return ResponseHelper::error([], "Invalid payment option selected.", 400);
            }
        } catch (Exception $e) {
            return ResponseHelper::error([], "Error : " . $e->getMessage());
        }


    }



    /**
     * Check if an order can be paid.
     *
     * @param Order $order
     * @return true|\Illuminate\Http\JsonResponse
     */
    private function canBePaid(Order $order)
    {
        switch ($order->status) {
            case 'pending':
                return true;

            case 'paid':
                return ResponseHelper::error([], "This order is already paid.", 400);

            case 'cancelled':
                return ResponseHelper::error([], "This order has been cancelled and cannot be paid.", 400);

            case 'shipped':
                return ResponseHelper::error([], "This order has already been shipped and cannot be paid.", 400);

            default:
                return ResponseHelper::error([], "This order cannot be paid. Current status: {$order->status}", 400);
        }
    }


    private function optionsAndMethods(Request $request)
    {
        $payOption = PaymentOptions::find($request->payment_option_id);

        if (in_array($payOption->key, ['pay-now', 'save-pay-later'])) {
            $validator = Validator::make($request->all(), [
                'payment_method_id' => 'required|numeric|exists:payment_methods,id',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error($validator->errors(), 'Failed to validate fields.', 422);
            }

            $paymentMethod = PaymentMethod::find($request->payment_method_id);

            if (!$paymentMethod || !$paymentMethod->enabled) {
                return ResponseHelper::error([], ($paymentMethod->display ?? 'This method') . ' is disabled for payment.', 400);
            }

            if ($paymentMethod->type === 'mno') {
                $validator = Validator::make($request->all(), [
                    'phone' => 'required|string|regex:/^\+255\d{9}$/',
                ], [
                    'phone.regex' => 'Phone number format should be +255XXXXXXXXX',
                ]);

                if ($validator->fails()) {
                    return ResponseHelper::error($validator->errors(), 'All mobile payments require a valid phone number.', 422);
                }

                $mobNetHelper = new MobileNetworkHelper();
                $network = $mobNetHelper->getNetworkByPrefix($request->phone);

                $userPayMethod = PaymentMethod::where('code', $network)->first();

                if (!$userPayMethod || !$userPayMethod->enabled) {
                    return ResponseHelper::error([], ($userPayMethod->display ?? 'This network') . ' is disabled for payment.', 400);
                }

                if ($userPayMethod->id !== $paymentMethod->id) {
                    return ResponseHelper::error([], "Phone number does not match the selected payment method ({$paymentMethod->display}).", 400);
                }
            }
        }
        return true;
    }




    private function handlePayNow(Order $order, Request $request)
    {
        $phoneNumber = $request->phone;
        $amount = $order->total_amount;

        $data = [
            'phone' => $phoneNumber,
            'amount' => $amount,
            'order_id' => $order->id,
        ];

        $payHelper = new PaymentHelper();
        $payMethod = PaymentMethod::find($request->payment_method_id);

        $response = $payHelper->initiatePayment($payMethod, $data);

        // Create payment record
        $payment = new Payment();
        $payment->payment_option_id = $request->payment_option_id;
        $payment->payment_method_id = $request->payment_method_id;
        $payment->amount = $amount;
        $payment->order_id = $order->id;
        $payment->user_id = $request->user()->id;
        $payment->msisdn = $phoneNumber;
        $payment->reference = $response['reference'] ?? null;

        if ($response['status'] === true) {
            $payment->status = 'pending'; // waiting for confirmation from Airtel or another provider
            $payment->notes = $response['message'] ?? 'Payment initiated successfully.';
            $payment->save();

            return ResponseHelper::success([
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
                'message' => $response['message'] ?? 'Payment initiated successfully.',
            ], 'Charge initiated.');
        } else {
            $payment->status = 'failed';
            $payment->notes = $response['message'] ?? 'Payment initiation failed';
            $payment->save();

            return ResponseHelper::error([
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
                'message' => $response['message'] ?? 'Payment initiation failed.',
            ], 'Payment initiation failed', 400);
        }

    }

    private function handleSavePayLater(Order $order, Request $request)
    {

    }

    private function handleRequestPayment(Order $order, Request $request)
    {

    }



}
