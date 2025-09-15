<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of enabled payment methods.
     */

    /**
     * @OA\Get(
     *     path="/payment/methods",
     *     tags={"Payments"},
     *     summary="Get list of available payment methods",
     *     description="Retrieve all enabled payment methods such as cards, banks, or MNOs.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of available payment methods",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of available payment methods"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", example="card"),
     *                     @OA\Property(property="display", type="string", example="Visa"),
     *                     @OA\Property(property="image", type="string", example="https://example.com/images/visa.png"),
     *                     @OA\Property(property="code", type="string", example="visa"),
     *                     @OA\Property(property="enabled", type="boolean", example=true)
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

    public function index()
    {
        $paymentMethods = PaymentMethod::where('enabled', true)->get();
        return ResponseHelper::success($paymentMethods, "List of available payment methods");
    }

    /**
     * Store a newly created resource in storage.
     */

    /**
     * @OA\Post(
     *     path="/payment/methods",
     *     tags={"Payments"},
     *     summary="Create a new payment method",
     *     description="Add a new payment method such as card, bank, or mobile network operator (MNO).",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type","display"},
     *             @OA\Property(property="type", type="string", enum={"card","bank","mno"}, example="card"),
     *             @OA\Property(property="display", type="string", example="Visa"),
     *             @OA\Property(property="image", type="string", nullable=true, example="https://example.com/images/visa.png")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment method added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment method added successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="type", type="string", example="card"),
     *                 @OA\Property(property="display", type="string", example="Visa"),
     *                 @OA\Property(property="image", type="string", example="https://example.com/images/visa.png"),
     *                 @OA\Property(property="code", type="string", example="visa"),
     *                 @OA\Property(property="enabled", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to validate fields"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="display", type="array",
     *                     @OA\Items(type="string", example="The display field has already been taken.")
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

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:card,bank,mno',
            'display' => 'required|string|unique:payment_methods,display',
            'image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields", 422);
        }

        $paymentMethod = PaymentMethod::create([
            'type' => $request->type,
            'display' => $request->display,
            'image' => $request->image,
            'code' => Str::slug($request->display),
            'enabled' => true,
        ]);

        return ResponseHelper::success($paymentMethod, "Payment method added successfully", 201);
    }

    /**
     * Display the specified resource.
     */

    /**
     * @OA\Get(
     *     path="/payment/methods/{id}",
     *     tags={"Payments"},
     *     summary="Get payment method details",
     *     description="Retrieve the details of a specific payment method by its ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the payment method",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment method details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment method details"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="type", type="string", example="card"),
     *                 @OA\Property(property="display", type="string", example="Visa"),
     *                 @OA\Property(property="image", type="string", example="https://example.com/images/visa.png"),
     *                 @OA\Property(property="code", type="string", example="visa"),
     *                 @OA\Property(property="enabled", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment method not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payment method not found"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */

    public function show(string $id)
    {
        $paymentMethod = PaymentMethod::find($id);

        if (!$paymentMethod) {
            return ResponseHelper::error([], "Payment method not found", 404);
        }

        return ResponseHelper::success($paymentMethod, "Payment method details");
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Patch(
     *     path="/payment/methods/{id}",
     *     tags={"Payments"},
     *     summary="Update a payment method",
     *     description="Update the details of an existing payment method by its ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the payment method",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", enum={"card","bank","mno"}, example="card"),
     *             @OA\Property(property="display", type="string", example="Visa"),
     *             @OA\Property(property="image", type="string", example="https://example.com/images/visa.png")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment method updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment method updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="type", type="string", example="card"),
     *                 @OA\Property(property="display", type="string", example="Visa"),
     *                 @OA\Property(property="image", type="string", example="https://example.com/images/visa.png"),
     *                 @OA\Property(property="code", type="string", example="visa"),
     *                 @OA\Property(property="enabled", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment method not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payment method not found"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to validate fields"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="display", type="array",
     *                     @OA\Items(type="string", example="The display field has already been taken.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    public function update(Request $request, string $id)
    {
        $paymentMethod = PaymentMethod::find($id);

        if (!$paymentMethod) {
            return ResponseHelper::error([], "Payment method not found", 404);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|required|string|in:card,bank,mno',
            'display' => 'sometimes|required|string|unique:payment_methods,display,' . $id,
            'image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields", 422);
        }

        $paymentMethod->update([
            'type' => $request->type ?? $paymentMethod->type,
            'display' => $request->display ?? $paymentMethod->display,
            'image' => $request->image ?? $paymentMethod->image,
            'code' => $request->display ? Str::slug($request->display) : $paymentMethod->code,
        ]);

        return ResponseHelper::success($paymentMethod, "Payment method updated successfully");
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     *     path="/payment/methods/{id}",
     *     tags={"Payments"},
     *     summary="Delete a payment method",
     *     description="Remove a payment method by its ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the payment method to delete",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment method deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment method deleted successfully"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment method not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payment method not found"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */

    public function destroy(string $id)
    {
        $paymentMethod = PaymentMethod::find($id);

        if (!$paymentMethod) {
            return ResponseHelper::error([], "Payment method not found", 404);
        }

        $paymentMethod->delete();

        return ResponseHelper::success([], "Payment method deleted successfully");
    }

    /**
     * List all payment methods (enabled + disabled).
     */

    /**
     * @OA\Get(
     *     path="/payment/methods/all",
     *     tags={"Payments"},
     *     summary="Get all payment methods",
     *     description="Retrieve a list of all payment methods, including disabled ones.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of all payment methods",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of all payment methods"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", example="card"),
     *                     @OA\Property(property="display", type="string", example="Visa"),
     *                     @OA\Property(property="code", type="string", example="visa"),
     *                     @OA\Property(property="enabled", type="boolean", example=true),
     *                     @OA\Property(property="image", type="string", example="https://example.com/images/visa.png"),
     *                     @OA\Property(property="created_at", type="string", example="2025-09-14T15:30:00Z"),
     *                     @OA\Property(property="updated_at", type="string", example="2025-09-14T15:30:00Z")
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    public function all()
    {
        $paymentMethods = PaymentMethod::all();
        return ResponseHelper::success($paymentMethods, 'List of all payment methods');
    }

    /**
     * Toggle status (enable/disable) of a payment method.
     */
    /**
 * @OA\Post(
 *     path="/payment/methods/{id}/status",
 *     tags={"Payments"},
 *     summary="Toggle payment method status",
 *     description="Enable or disable a payment method by ID.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the payment method",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Payment method status updated",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Visa enabled successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="type", type="string", example="card"),
 *                 @OA\Property(property="display", type="string", example="Visa"),
 *                 @OA\Property(property="code", type="string", example="visa"),
 *                 @OA\Property(property="enabled", type="boolean", example=true),
 *                 @OA\Property(property="image", type="string", example="https://example.com/images/visa.png"),
 *                 @OA\Property(property="created_at", type="string", example="2025-09-14T15:30:00Z"),
 *                 @OA\Property(property="updated_at", type="string", example="2025-09-14T15:35:00Z")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=404, description="Payment method not found")
 * )
 */

    public function status($id)
    {
        $paymentMethod = PaymentMethod::find($id);

        if (!$paymentMethod) {
            return ResponseHelper::error([], "Payment method not found", 404);
        }

        $paymentMethod->enabled = !$paymentMethod->enabled;
        $paymentMethod->save();

        $message = $paymentMethod->enabled
            ? $paymentMethod->display . " enabled successfully"
            : $paymentMethod->display . " disabled successfully";

        return ResponseHelper::success($paymentMethod, $message);
    }
}
