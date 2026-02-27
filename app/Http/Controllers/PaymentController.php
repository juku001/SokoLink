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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{


    /**
     * @OA\Get(
     *     path="/payments",
     *     summary="Get list of payments for authenticated user",
     *     description="Retrieve a paginated list of payments with optional filters for payment method, status, and search by order reference or payment reference.",
     *     operationId="listPayments",
     *     tags={"Payments"},
     *     security={{"bearerAuth": {}}},
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
        $authId = Auth::id();

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
     *     tags={"Cart"},
     *     security={{"bearerAuth": {}}},
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
     *                 @OA\Property(property="address_phone", type="string", example="+255712345678"),
     *                 @OA\Property(property="address", type="string", example="123 Main Street"),
     *                 @OA\Property(property="region_id", type="integer", example=1),
     *                 @OA\Property(property="payment_method_id", type="integer", example=2),
     *                 @OA\Property(property="payment_option_id", type="integer", example=1)
     *             )
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
            'address_phone' => 'nullable|string|regex:/^\+255\d{9}$/',
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



        // DB::beginTransaction();
        try {
            $authId = Auth::id();

            $cart = Cart::with('items.product.store')
                ->where('buyer_id', $authId)
                ->first();

            if (!$cart || $cart->items->count() == 0) {
                return ResponseHelper::error([], 'Please add items to cart first.', 400);
            }

            $subTotal = $cart->items->reduce(function ($carry, $item) {
                return $carry + ($item->price * $item->quantity);
            }, 0);
            $shipping = 0;
            $total = $subTotal + $shipping;

            $checkOptionAndMethods = $this->optionsAndMethods($request);
            if ($checkOptionAndMethods !== true) {
                return $checkOptionAndMethods;
            }

            // Store checkout details in session/temp storage for order creation after payment
            $checkoutData = [
                'fullname' => $request->fullname,
                'phone' => $request->phone,
                'address_phone' => $request->address_phone,
                'address' => $request->address,
                'region_id' => $request->region_id,
                'payment_option_id' => $request->payment_option_id,
                'payment_method_id' => $request->payment_method_id,
                'total_amount' => $total,
                'shipping_cost' => $shipping,
            ];

            // Store checkout data in session or cache
            session(['checkout_data_' . $cart->id => $checkoutData]);

            $payOption = PaymentOptions::find($request->payment_option_id);
            switch ($payOption->key) {
                case 'pay-now':
                    return $this->handlePayNowForCart($cart, $request, $total);
                case 'save-pay-later':
                    return $this->handleSavePayLaterForCart($cart, $request, $total);
                case 'request-payment':
                    return $this->handleRequestPaymentForCart($cart, $request, $total);
                default:
                    return ResponseHelper::error([], "Invalid payment option selected.", 400);
            }
        }
        // catch (QueryException $e) {
        //     return ResponseHelper::error([], "Error : " . $e->getMessage(), 400);
        // }
        catch (Exception $e) {
            return ResponseHelper::error([], "Error: " . $e->getMessage(), 500);
        }
    }






    /**
     * @OA\Get(
     *     path="/payments/{id}",
     *     tags={"Payments"},
     *     summary="Get payment details",
     *     description="Retrieve a single payment by its ID.",
     *     operationId="getPaymentById",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment ID",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment details retrieved successfully",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Payment details"),
     *              @OA\Property(property="code", type="integer", example=200),
     *              @OA\Property(property="data", ref="#/components/schemas/Payment")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payment not found"),
     *             @OA\Property(property="code", type="integer", example=404)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function show(string $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return ResponseHelper::error([], 'Payment not found', 404);
        }

        return ResponseHelper::success($payment, 'Payment details', 200);
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

        if (!$payOption) {
            return ResponseHelper::error([], 'Invalid payment option selected.', 400);
        }

        if (in_array($payOption->key, ['pay-now', 'save-pay-later'])) {
            $validator = Validator::make($request->all(), [
                'payment_method_id' => 'required|numeric|exists:payment_methods,id',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error($validator->errors(), 'Failed to validate fields.', 422);
            }

            $paymentMethod = PaymentMethod::find($request->payment_method_id);

            if (!$paymentMethod) {
                return ResponseHelper::error([], 'Payment method not found.', 400);
            }

            if (!$paymentMethod->enabled) {
                return ResponseHelper::error([], $paymentMethod->display . ' is disabled for payment.', 400);
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




    // private function handlePayNowForCart(Cart $cart, Request $request, $amount)
    // {
    //     $phoneNumber = $request->phone;
    //     Log::info('started pay now for ' . $phoneNumber);
    //     $data = [
    //         'phone' => $phoneNumber,
    //         'amount' => $amount,
    //         'cart_id' => $cart->id,
    //     ];

    //     $payHelper = new PaymentHelper();
    //     $payMethod = PaymentMethod::find($request->payment_method_id);

    //     $response = $payHelper->initiatePayment($payMethod, $data);


    //     // Create payment record linked to cart
    //     $payment = new Payment();
    //     $payment->payment_option_id = $request->payment_option_id;
    //     $payment->payment_method_id = $request->payment_method_id;
    //     $payment->amount = $amount;
    //     $payment->cart_id = $cart->id;
    //     $payment->user_id = $request->user()->id;
    //     $payment->msisdn = $phoneNumber;
    //     $payment->reference = $response['reference'] ?? null;

    //     if ($response['status'] === true) {
    //         $payment->status = 'pending'; // waiting for confirmation from Airtel or another provider
    //         $payment->notes = $response['message'] ?? 'Payment initiated successfully.';
    //         $payment->save();

    //         return ResponseHelper::success([
    //             'payment_id' => $payment->id,
    //             'reference' => $payment->reference,
    //             'message' => $response['message'] ?? 'Payment initiated successfully.',
    //         ], 'Charge initiated.');
    //     } else {
    //         $payment->status = 'failed';
    //         $payment->notes = $response['message'] ?? 'Payment initiation failed';
    //         $payment->save();

    //         return ResponseHelper::error([
    //             'payment_id' => $payment->id,
    //             'reference' => $payment->reference,
    //             'message' => $response['message'] ?? 'Payment initiation failed.',
    //         ], 'Payment initiation failed', 400);
    //     }
    // }




    private function handlePayNowForCart(Cart $cart, Request $request, $amount)
    {
        DB::beginTransaction();

        try {
            $phoneNumber = $request->phone;

            Log::info('Started pay now for ' . $phoneNumber, [
                'cart_id' => $cart->id,
                'user_id' => $request->user()->id,
            ]);

            $data = [
                'phone' => $phoneNumber,
                'amount' => $amount,
                'cart_id' => $cart->id,
            ];

            $payMethod = PaymentMethod::findOrFail($request->payment_method_id);

            $payHelper = new PaymentHelper();
            $response = $payHelper->initiatePayment($payMethod, $data);

            if (!is_array($response)) {
                throw new Exception('Invalid payment gateway response');
            }

            // Create payment record
            $payment = new Payment();
            $payment->payment_option_id = $request->payment_option_id;
            $payment->payment_method_id = $request->payment_method_id;
            $payment->amount = $amount;
            $payment->cart_id = $cart->id;
            $payment->user_id = $request->user()->id;
            $payment->msisdn = $phoneNumber;
            $payment->reference = $response['reference'] ?? null;


            if (($response['status'] ?? false) === true) {

                $payment->status = 'pending';
                $payment->notes = $response['message'] ?? 'Payment initiated successfully.';
                $payment->save();
                DB::commit();

                return ResponseHelper::success([
                    'payment_id' => $payment->id,
                    'reference' => $payment->reference,
                    'message' => $payment->notes,
                ], 'Charge initiated.');
            } else {


                $payment->status = 'failed';
                $payment->notes = $response['message'] ?? 'Payment initiation failed';
                $payment->save();

                DB::commit();

                return ResponseHelper::error([
                    'payment_id' => $payment->id,
                    'reference' => $payment->reference,
                    'message' => $payment->notes,
                ], 'Payment initiation failed', 400);
            }
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Payment initiation crashed', [
                'error' => $e->getMessage(),
                'cart_id' => $cart->id ?? null,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return ResponseHelper::error(
                [],
                'Something went wrong while initiating payment. Please try again.',
                500
            );
        }
    }

    private function handleSavePayLaterForCart(Cart $cart, Request $request, $amount)
    {
        // Implementation for save pay later
        return ResponseHelper::error([], 'Save pay later not implemented yet.', 501);
    }

    private function handleRequestPaymentForCart(Cart $cart, Request $request, $amount)
    {
        // Implementation for request payment
        return ResponseHelper::error([], 'Request payment not implemented yet.', 501);
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

        if (!$authResponse->successful()) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get token',
                'data' => $authResponse->json()
            ]);
        }

        $authData = $authResponse->json();

        if (!isset($authData['access_token'])) {
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

        return $paymentResponse->json();
    }

    /**
     * Handle Selcom payment callback/webhook
     *
     * @OA\Post(
     *     path="/payments/callback/selcom",
     *     summary="Selcom payment callback handler",
     *     description="Receives payment status updates from Selcom payment gateway",
     *     operationId="selcomCallback",
     *     tags={"Payments"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"result","resultcode","order_id","transid","reference","amount","payment_status"},
     *             @OA\Property(property="result", type="string", example="SUCCESS"),
     *             @OA\Property(property="resultcode", type="string", example="000"),
     *             @OA\Property(property="order_id", type="string", example="602021152"),
     *             @OA\Property(property="transid", type="string", example="7945454515"),
     *             @OA\Property(property="reference", type="string", example="856266164161"),
     *             @OA\Property(property="channel", type="string", example="TIGOPESATZ"),
     *             @OA\Property(property="amount", type="string", example="10000"),
     *             @OA\Property(property="phone", type="string", example="255000000001"),
     *             @OA\Property(property="payment_status", type="string", example="COMPLETED")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Callback processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Callback processed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payment not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid callback data",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid callback data")
     *         )
     *     )
     * )
     */
    public function selcomCallback(Request $request)
    {
        try {
            // Log the incoming callback for debugging
            Log::info('Selcom callback received', [
                'payload' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Validate required fields
            $validator = Validator::make($request->all(), [
                'result' => 'required|string',
                'resultcode' => 'required|string',
                'order_id' => 'required|string',
                'transid' => 'required|string',
                'reference' => 'required|string',
                'amount' => 'required|numeric',
                'payment_status' => 'required|string'
            ]);

            if ($validator->fails()) {
                Log::warning('Selcom callback validation failed', [
                    'errors' => $validator->errors(),
                    'payload' => $request->all()
                ]);
                return ResponseHelper::error($validator->errors(), 'Invalid callback data', 422);
            }

            // Find payment by reference
            $payment = Payment::where('reference', $request->reference)->first();

            if (!$payment) {
                Log::warning('Selcom callback: Payment not found', [
                    'reference' => $request->reference,
                    'payload' => $request->all()
                ]);
                return ResponseHelper::error([], 'Payment not found', 404);
            }

            // Update payment status based on Selcom response
            $newStatus = $this->mapSelcomStatusToPaymentStatus($request->payment_status, $request->result);
            $oldStatus = $payment->status;

            DB::beginTransaction();

            try {
                $payment->status = $newStatus;
                $payment->transaction_id = $request->transid;
                $payment->notes = "Selcom callback: {$request->result} - {$request->payment_status}";

                // Add callback metadata
                $callbackData = [
                    'selcom_order_id' => $request->order_id,
                    'selcom_transid' => $request->transid,
                    'selcom_channel' => $request->input('channel'),
                    'selcom_result' => $request->result,
                    'selcom_resultcode' => $request->resultcode,
                    'callback_received_at' => now()->toISOString()
                ];

                $payment->callback_data = json_encode($callbackData);
                $payment->save();

                // If payment is successful, process order creation or update
                if ($newStatus === 'successful' && $payment->cart_id) {
                    $this->processSuccessfulCartPayment($payment);
                }

                DB::commit();

                Log::info('Selcom callback processed successfully', [
                    'payment_id' => $payment->id,
                    'reference' => $request->reference,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'transid' => $request->transid
                ]);

                return ResponseHelper::success([
                    'payment_id' => $payment->id,
                    'status' => $newStatus
                ], 'Callback processed successfully');
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Error processing Selcom callback', [
                    'error' => $e->getMessage(),
                    'payment_id' => $payment->id ?? null,
                    'reference' => $request->reference,
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        } catch (Throwable $e) {
            Log::error('Selcom callback processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseHelper::error(
                [],
                'Callback processing failed',
                500
            );
        }
    }

    /**
     * Map Selcom payment status to internal payment status
     */
    private function mapSelcomStatusToPaymentStatus(string $paymentStatus, string $result): string
    {
        // Map Selcom statuses to internal payment statuses
        if ($result === 'SUCCESS' && $paymentStatus === 'COMPLETED') {
            return 'successful';
        }

        if ($result === 'FAILED' || $paymentStatus === 'FAILED') {
            return 'failed';
        }

        if ($paymentStatus === 'PENDING' || $paymentStatus === 'PROCESSING') {
            return 'pending';
        }

        if ($paymentStatus === 'CANCELLED') {
            return 'cancelled';
        }

        // Default to failed for unknown statuses
        Log::warning('Unknown Selcom payment status', [
            'payment_status' => $paymentStatus,
            'result' => $result
        ]);

        return 'failed';
    }

    /**
     * Process successful cart payment - create order from cart
     */
    private function processSuccessfulCartPayment(Payment $payment)
    {
        try {
            $cart = Cart::with('items.product.store')->find($payment->cart_id);

            if (!$cart) {
                Log::warning('Cart not found for successful payment', ['payment_id' => $payment->id]);
                return;
            }

            // Get checkout data from session
            $checkoutData = session('checkout_data_' . $cart->id);

            if (!$checkoutData) {
                Log::warning('Checkout data not found for successful payment', [
                    'payment_id' => $payment->id,
                    'cart_id' => $cart->id
                ]);
                return;
            }

            // Create order from cart
            $order = new Order();
            $order->order_ref = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $order->buyer_id = $cart->buyer_id;
            $order->total_amount = $payment->amount;
            $order->shipping_cost = $checkoutData['shipping_cost'] ?? 0;
            $order->status = 'paid';
            $order->payment_id = $payment->id;

            // Shipping address
            $address = new Address();
            $address->fullname = $checkoutData['fullname'];
            $address->phone = $checkoutData['phone'];
            $address->address_phone = $checkoutData['address_phone'] ?? null;
            $address->address = $checkoutData['address'];
            $address->region_id = $checkoutData['region_id'];
            $address->save();

            $order->shipping_address_id = $address->id;
            $order->save();

            // Create order items from cart items
            foreach ($cart->items as $cartItem) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $cartItem->product_id;
                $orderItem->quantity = $cartItem->quantity;
                $orderItem->price = $cartItem->price;
                $orderItem->total = $cartItem->price * $cartItem->quantity;
                $orderItem->save();
            }

            // Create order status history
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'paid',
                'notes' => 'Order created and paid via Selcom callback',
                'changed_by' => null
            ]);

            // Update payment with order reference
            $payment->order_id = $order->id;
            $payment->save();

            // Clear cart
            $cart->items()->delete();
            $cart->delete();

            // Clear checkout session data
            session()->forget('checkout_data_' . $cart->id);

            Log::info('Order created successfully from Selcom payment', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'order_ref' => $order->order_ref
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create order from successful Selcom payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't rethrow - payment was successful, order creation failure shouldn't fail the callback
        }
    }
}
