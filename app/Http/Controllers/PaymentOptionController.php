<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\PaymentOptions;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentOptionController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware(['auth:sanctum', 'user.type:super_admin'], only: ['store', 'update', 'destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/payment/options",
     *     tags={"Payments"},
     *     summary="List all payment options",
     *     description="Retrieve a list of available payment options.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of payment options retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of payment options"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Mpesa"),
     *                     @OA\Property(property="description", type="string", example="Mobile money payment option"),
     *                     @OA\Property(property="key", type="string", example="mpesa")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */

    public function index(Request $request)
    {
        $paymentOptions = PaymentOptions::all();

        return ResponseHelper::success($paymentOptions, 'List of payment options');
    }

    /**
     * Store a newly created resource in storage.
     */

    /**
     * @OA\Post(
     *     path="/payment/options",
     *     tags={"Payments"},
     *     summary="Create a new payment option",
     *     description="Add a new payment option to the system.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","description"},
     *             @OA\Property(property="name", type="string", example="Mpesa"),
     *             @OA\Property(property="description", type="string", example="Mobile money payment option")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment option created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment option added successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Mpesa"),
     *                 @OA\Property(property="description", type="string", example="Mobile money payment option"),
     *                 @OA\Property(property="key", type="string", example="mpesa")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to validate fields"),
     *             @OA\Property(property="data", type="object", example={"name": {"The name field is required."}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:payment_options,name',
            'description' => 'required|string'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields", 422);
        }

        $paymentOption = PaymentOptions::create([
            'name' => $request->name,
            'description' => $request->description,
            'key' => Str::slug($request->name),
        ]);

        return ResponseHelper::success($paymentOption, 'Payment option added successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/payment/options/{id}",
     *     tags={"Payments"},
     *     summary="Get payment option details",
     *     description="Retrieve details of a specific payment option by ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the payment option",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment option details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment option details"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Mpesa"),
     *                 @OA\Property(property="description", type="string", example="Mobile money payment option"),
     *                 @OA\Property(property="key", type="string", example="mpesa")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment option not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payment option not found"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */

    public function show(string $id)
    {
        $paymentOption = PaymentOptions::find($id);

        if (!$paymentOption) {
            return ResponseHelper::error([], "Payment option not found", 404);
        }

        return ResponseHelper::success($paymentOption, 'Payment option details');
    }

    /**
     * Update the specified resource in storage.
     */

    /**
     * @OA\Patch(
     *     path="/payment/options/{id}",
     *     tags={"Payments"},
     *     summary="Update a payment option",
     *     description="Update details of an existing payment option by ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the payment option",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Tigo Pesa"),
     *             @OA\Property(property="description", type="string", example="Updated mobile money payment option")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment option updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment option updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Tigo Pesa"),
     *                 @OA\Property(property="description", type="string", example="Updated mobile money payment option"),
     *                 @OA\Property(property="key", type="string", example="tigo-pesa")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment option not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payment option not found"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to validate fields"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */

    public function update(Request $request, string $id)
    {
        $paymentOption = PaymentOptions::find($id);

        if (!$paymentOption) {
            return ResponseHelper::error([], "Payment option not found", 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|unique:payment_options,name,' . $id,
            'description' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields", 422);
        }

        $paymentOption->update([
            'name' => $request->name ?? $paymentOption->name,
            'description' => $request->description ?? $paymentOption->description,
            'key' => $request->name ? Str::slug($request->name) : $paymentOption->key,
        ]);

        return ResponseHelper::success($paymentOption, 'Payment option updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */

    /**
     * @OA\Delete(
     *     path="/payment/options/{id}",
     *     tags={"Payments"},
     *     summary="Delete a payment option",
     *     description="Remove a payment option by its ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the payment option",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment option deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment option deleted successfully"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment option not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payment option not found"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */

    public function destroy(string $id)
    {
        $paymentOption = PaymentOptions::find($id);

        if (!$paymentOption) {
            return ResponseHelper::error([], "Payment option not found", 404);
        }

        $paymentOption->delete();

        return ResponseHelper::success([], 'Payment option deleted successfully');
    }
}
