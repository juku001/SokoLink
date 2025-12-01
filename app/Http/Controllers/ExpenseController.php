<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Expense;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{

    /**
     * @OA\Get(
     *     path="/expenses",
     *     tags={"Expenses"},
     *     summary="Get a list of seller expenses",
     *     description="Retrieve expenses for the authenticated seller with optional filters: date range, expense type, and supplier search",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Start date for filtering expenses (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="End date for filtering expenses (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="expense_type_id",
     *         in="query",
     *         description="Filter by expense type ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by supplier name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of expenses retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of expenses"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="seller_id", type="integer", example=5),
     *                         @OA\Property(property="expense_type_id", type="integer", example=2),
     *                         @OA\Property(property="supplier", type="string", example="ABC Supplies"),
     *                         @OA\Property(property="amount", type="number", format="float", example=150.75),
     *                         @OA\Property(property="expense_date", type="string", format="date", example="2025-09-14"),
     *                         @OA\Property(property="description", type="string", example="Office stationery")
     *                     )
     *                 ),
     *                 @OA\Property(property="last_page", type="integer", example=3),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=42)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/401"
     *     )
     * )
     */

    public function index(Request $request)
    {
        $authId = auth()->id();

        $query = Expense::where('seller_id', $authId);

        // Filter by date range
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('expense_date', [
                $request->from_date,
                $request->to_date
            ]);
        }

        // Filter by expense type
        if ($request->filled('expense_type_id')) {
            $query->where('expense_type_id', $request->expense_type_id);
        }

        // Search by supplier name
        if ($request->filled('search')) {
            $query->where('supplier', 'LIKE', '%' . $request->search . '%');
        }

        $expenses = $query->orderBy('expense_date', 'desc')->paginate(15);

        return ResponseHelper::success($expenses, "List of expenses");
    }


    /**
     * @OA\Post(
     *     path="/expenses",
     *     tags={"Expenses"},
     *     summary="Record a new expense",
     *     description="Create a new expense for the authenticated seller",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"supplier","amount","expense_date","status"},
     *             @OA\Property(property="supplier", type="string", maxLength=255, example="ABC Supplies"),
     *             @OA\Property(property="expense_type_id", type="integer", example=2),
     *             @OA\Property(property="store_id", type="integer", example=5),
     *             @OA\Property(property="amount", type="number", format="float", example=150.75),
     *             @OA\Property(property="expense_date", type="string", format="date", example="2025-09-14"),
     *             @OA\Property(property="status", type="string", enum={"pending","paid","overdue"}, example="pending")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Expense recorded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expense recorded successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="seller_id", type="integer", example=5),
     *                 @OA\Property(property="supplier", type="string", example="ABC Supplies"),
     *                 @OA\Property(property="expense_type_id", type="integer", example=2),
     *                 @OA\Property(property="store_id", type="integer", example=5),
     *                 @OA\Property(property="amount", type="number", format="float", example=150.75),
     *                 @OA\Property(property="expense_date", type="string", format="date", example="2025-09-14"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-14T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-14T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error: Database error or exception message"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier' => 'required|string|max:255',
            'expense_type_id' => 'required|exists:expense_types,id',
            'store_id' => 'nullable|exists:stores,id',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'status' => 'required|in:pending,paid,overdue',
        ],[
            'status.in' => 'Status should be pending, paid or overdue'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields',
                422
            );
        }

        try {
            $validated = $validator->validated();
            $sellerId = auth()->id();
            $seller = Seller::where('user_id', $sellerId)->first();


            $expense = Expense::create([
                'supplier' => $validated['supplier'],
                'expense_type_id' => $validated['expense_type_id'] ?? null,
                'store_id' => $validated['store_id'] ?? $seller->active_store,
                'seller_id' => $sellerId, // seller = authenticated user
                'amount' => $validated['amount'],
                'expense_date' => $validated['expense_date'],
                'status' => $validated['status'],
            ]);

            return ResponseHelper::success($expense, 'Expense recorded successfully', 201);
        } catch (\Exception $e) {
            return ResponseHelper::error([], "Error: " . $e->getMessage(), 500);
        }
    }


    /**
     * @OA\Patch(
     *     path="/expenses/{id}",
     *     tags={"Expenses"},
     *     summary="Update an existing expense",
     *     description="Update the details of an expense for the authenticated seller",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the expense to update",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="supplier", type="string", maxLength=255, example="ABC Supplies"),
     *             @OA\Property(property="expense_type_id", type="integer", example=2),
     *             @OA\Property(property="store_id", type="integer", example=5),
     *             @OA\Property(property="amount", type="number", format="float", example=150.75),
     *             @OA\Property(property="expense_date", type="string", format="date", example="2025-09-14"),
     *             @OA\Property(property="status", type="string", enum={"pending","paid","overdue"}, example="paid"),
     *             @OA\Property(property="closed_date", type="string", format="date", example="2025-09-15")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expense updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expense updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="seller_id", type="integer", example=5),
     *                 @OA\Property(property="supplier", type="string", example="ABC Supplies"),
     *                 @OA\Property(property="expense_type_id", type="integer", example=2),
     *                 @OA\Property(property="store_id", type="integer", example=5),
     *                 @OA\Property(property="amount", type="number", format="float", example=150.75),
     *                 @OA\Property(property="expense_date", type="string", format="date", example="2025-09-14"),
     *                 @OA\Property(property="status", type="string", example="paid"),
     *                 @OA\Property(property="closed_date", type="string", format="date", example="2025-09-15"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-14T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-15T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Expense not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Expense not found"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error: Database error or exception message"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */


    public function update(Request $request, string $id)
    {
        $expense = Expense::where('seller_id', auth()->id())->find($id);

        if (!$expense) {
            return ResponseHelper::error([], "Expense not found", 404);
        }

        $validator = Validator::make($request->all(), [
            'supplier' => 'sometimes|required|string|max:255',
            'expense_type_id' => 'nullable|exists:expense_types,id',
            'store_id' => 'nullable|exists:stores,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'expense_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|in:pending,paid,overdue',
            'closed_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields',
                422
            );
        }

        try {
            $expense->update($validator->validated());

            return ResponseHelper::success($expense, 'Expense updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error([], "Error: " . $e->getMessage(), 500);
        }
    }



}
