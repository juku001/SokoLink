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
use Http;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Validator;

class PaymentController extends Controller
{


    /**
     * @OA\Get(
     *     path="/payments",
     *     summary="Get list of payments for authenticated user",
     *     description="Retrieve a paginated list of payments with optional filters for payment method, status, and search by order reference or payment reference.",
     *     operationId="listPayments",
     *     tags={"Payments"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="payment_method_id",
     *         in="query",
     *         description="Filter payments by payment method ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter payments by status (e.g., pending, successful, failed)",
     *         required=false,
     *         @OA\Schema(type="string", example="pending")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search payments by order reference or payment reference",
     *         required=false,
     *         @OA\Schema(type="string", example="ORD-20250916-ABC123")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of payments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of payments"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="current_page",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=101),
     *                         @OA\Property(property="reference", type="string", example="PAY-20250916-XYZ123"),
     *                         @OA\Property(property="amount", type="number", format="float", example=50000),
     *                         @OA\Property(property="status", type="string", example="pending"),
     *                         @OA\Property(property="order", type="object"),
     *                         @OA\Property(property="paymentMethod", type="object")
     *                     )
     *                 ),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=50),
     *                 @OA\Property(property="total", type="integer", example=250)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access",
     *         ref="#/components/responses/401"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $authId = auth()->user()->id;

        $query = Payment::with(['order', 'paymentMethod'])
            ->where("user_id", $authId);

        if ($request->has('payment_method_id') && !empty($request->payment_method_id)) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'LIKE', "%{$search}%")
                    ->orWhereHas('order', function ($sub) use ($search) {
                        $sub->where('order_ref', 'LIKE', "%{$search}%");
                    });
            });
        }

        $payments = $query->latest()->paginate(50);

        return ResponseHelper::success($payments, 'List of payments');
    }




    /**
     * @OA\Post(
     *     path="/checkout",
     *     summary="Checkout the cart and create an order",
     *     description="Transfers the buyer's cart items into an order with shipping address and payment details.",
     *     operationId="checkout",
     *     tags={"Orders"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"fullname","phone","address","region_id","payment_method_id","payment_option_id"},
     *                 @OA\Property(property="fullname", type="string", example="John Doe"),
     *                 @OA\Property(property="phone", type="string", example="+255712345678"),
     *                 @OA\Property(property="address", type="string", example="123 Main Street"),
     *                 @OA\Property(property="region_id", type="integer", example=1),
     *                 @OA\Property(property="payment_method_id", type="integer", example=2),
     *                 @OA\Property(property="payment_option_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Order placed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order placed successfully"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="order_id", type="integer", example=101),
     *                 @OA\Property(property="total", type="number", format="float", example=50000),
     *                 @OA\Property(property="subtotal", type="number", format="float", example=45000),
     *                 @OA\Property(property="shipping", type="number", format="float", example=5000),
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
     *         response=400,
     *         description="Cart empty or query error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Please add items to cart first."),
     *             @OA\Property(property="code", type="integer", example=400),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         ref="#/components/responses/422"
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */

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
            $shipping = 0;
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





    /**
     * @OA\Post(
     *     path="/payment/process",
     *     summary="Initiate payment for an order",
     *     description="Initiates a payment process for a specific order using a selected payment option.",
     *     operationId="initiatePayment",
     *     tags={"Payments"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="order_id", type="integer", example=101, description="ID of the order to pay for"),
     *             @OA\Property(property="payment_option_id", type="integer", example=1, description="ID of the selected payment option")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment process initiated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment initiated successfully"),
     *             @OA\Property(property="code", type="integer", example=200),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid payment option or cannot pay order",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid payment option selected."),
     *             @OA\Property(property="code", type="integer", example=400),
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
     *         response=422,
     *         description="Validation errors",
     *         ref="#/components/responses/422"
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */
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




    public function testing(Request $request)
    {
        // Step 1: Get token
        $authUrl = env('AIRTEL_BASE_URL') . 'auth/oauth2/token';

        $authBody = [
            "client_id" => env('AIRTEL_CLIENT_ID'),
            "client_secret" => env('AIRTEL_CLIENT_SECRET'),
            "grant_type" => "client_credentials"
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'X-Country' => 'TZ',
            'X-Currency' => 'TZS',
        ];

        $authResponse = Http::withOptions(['verify' => false])
            ->withHeaders($headers)
            ->post($authUrl, $authBody);

        $authData = $authResponse->json();

        if (!$authResponse->ok() || !isset($authData['access_token'])) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get token',
                'data' => $authData
            ]);
        }

        $token = $authData['access_token'];

        // Step 2: Payment request
         $reference = 'REF' . now()->format('YmdHis') . rand(1000, 9999);
        $paymentUrl = env('AIRTEL_BASE_URL') . 'merchant/v1/payments/';

        $paymentBody = [
            "reference" => $reference,
            "subscriber" => [
                "country" => "TZ",
                "currency" => "TZS",
                "msisdn" => $request->phone
            ],
            "transaction" => [
                "amount" => 1000,
                "country" => "TZ",
                "currency" => "TZS",
                "id" => $reference
            ]
        ];

        $paymentResponse = Http::withOptions(['verify' => false])
            ->withHeaders(array_merge($headers, [
                'Authorization' => 'Bearer ' . $token,
            ]))
            ->post($paymentUrl, $paymentBody);

        return response()->json($paymentResponse->json());
    }



}
