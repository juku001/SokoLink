<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerManagementController extends Controller
{


    /**
     * @OA\Get(
     *     path="/admin/customers",
     *     summary="Get list of customers",
     *     description="Retrieve a list of customers with optional search by name, email, or phone",
     *     tags={"Admin"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for name, email, or phone",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of customers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of customers"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="phone", type="string", example="+255123456789"),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="last_active", type="string", format="date-time", example="2025-09-16T12:34:56"),
     *                     @OA\Property(property="purchases", type="integer", example=5)
     *                 )
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function index(Request $request)
    {
        $query = User::with([
            'payments' => function ($q) {
                $q->latest();
            }
        ])->where('role', 'buyer');


        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->get();

        $data = $customers->map(function ($customer) {
            $lastActive = $customer->payments->isNotEmpty()
                ? $customer->payments->first()->created_at
                : $customer->created_at;

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'status' => $customer->status,
                'last_active' => $lastActive,
                'purchases' => $customer->payments->count(),
            ];
        });

        return ResponseHelper::success($data, "List of customers");
    }


    /**
     * @OA\Get(
     *     path="/admin/customers/{id}",
     *     summary="Get customer details",
     *     description="Retrieve detailed information for a specific customer including their payments and orders",
     *     tags={"Admin"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the customer",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer details"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+255123456789"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="payments", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=101),
     *                         @OA\Property(property="amount", type="number", format="float", example=2500),
     *                         @OA\Property(property="status", type="string", example="success"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-16T12:00:00"),
     *                         @OA\Property(property="order", type="object",
     *                             @OA\Property(property="id", type="integer", example=501),
     *                             @OA\Property(property="status", type="string", example="completed")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found."),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function show($id)
    {
        $user = User::with('payments.order')->find($id);
        if (!$user) {
            return ResponseHelper::error([], "User not found.", 404);
        }

        return ResponseHelper::success($user, 'Customer details');


    }
}
