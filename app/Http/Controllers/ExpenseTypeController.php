<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\ExpenseType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Validator;

class ExpenseTypeController extends Controller implements HasMiddleware
{

    public static function middleware()
    {
        return [
            new Middleware(
                ['auth:sanctum', 'user.type:super_admin'],
                only: ['store', 'update', 'destroy']
            )
        ];
    }

    /**
     * Display a listing of the resource.
     */


    /** 
     * @OA\Get(
     *     path="/expense/types",
     *     tags={"Expense Types"},
     *     summary="Get all expense types",
     *     description="Retrieve a list of all expense types",
     *     @OA\Response(
     *         response=200,
     *         description="List of expense types",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of expenses type"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Office Supplies"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-14T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-14T12:00:00Z")
     *                 )
     *             )
     *         )
     *     )
     * )
     **/
    public function index()
    {
        $expenseTypes = ExpenseType::all();
        return ResponseHelper::success($expenseTypes, 'List of expenses type');
    }

    /**
     * Store a newly created resource in storage.
     */

    /**
     * @OA\Post(
     *     path="/expense/types",
     *     tags={"Expense Types"},
     *     summary="Create a new expense type",
     *     description="Add a new expense type",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Office Supplies")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Expense Type created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Type added successful"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Office Supplies")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:expense_types,name',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields', 422);
        }

        $exType = ExpenseType::create($request->all());


        return ResponseHelper::success($exType, "Type added successful", 201);
    }

    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/expense/types/{id}",
     *     tags={"Expense Types"},
     *     summary="Get a single expense type",
     *     description="Retrieve details of a specific expense type by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the expense type",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expense Type details",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expense Type details"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Office Supplies")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Expense Type not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Expense Type not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     )
     * )
     */

    public function show(string $id)
    {
        $expenseType = ExpenseType::find($id);
        if (!$expenseType) {
            return ResponseHelper::error([], "Expense Type not found", 404);
        }
        return ResponseHelper::success($expenseType, "Expense Type details", 200);
    }

    /**
     * Update the specified resource in storage.
     */

    /**
     * @OA\Put(
     *     path="/expense/types/{id}",
     *     tags={"Expense Types"},
     *     summary="Update an expense type",
     *     description="Update the name of a specific expense type",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the expense type",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Office Equipment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expense Type updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expense Type updated successfully"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Office Equipment")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {


        $expenseType = ExpenseType::find($id);

        if (!$expenseType) {
            return ResponseHelper::error([], 'Expense Type not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:expense_types,name,' . $expenseType->id,
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields',
                422
            );
        }

        try {
            $data = [
                'name' => $request->name
            ];

            $expenseType->update($data);

            return ResponseHelper::success($expenseType, 'Expense Type updated successfully');

        } catch (Exception $e) {
            return ResponseHelper::error(
                [],
                'Error: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    /**
     * @OA\Delete(
     *     path="/expense/types/{id}",
     *     tags={"Expense Types"},
     *     summary="Delete an expense type",
     *     description="Remove a specific expense type from the system",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the expense type",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expense Type deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expense Type deleted successful."),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Office Supplies")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Expense Type not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Expense Type not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $expenseType = ExpenseType::find($id);
        if (!$expenseType) {
            return ResponseHelper::error([], "Expense Type not found", 404);
        }
        $expenseType->delete();
        return ResponseHelper::success($expenseType, "Expense Type deleted successful.", 200);
    }
}
