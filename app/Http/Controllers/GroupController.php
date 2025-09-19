<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Contact;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Str;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/groups",
     *     tags={"Contacts"},
     *     summary="Get all groups",
     *     description="Retrieve a list of all contact groups.",
     *     operationId="getGroups",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of groups retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of all groups"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="VIP Customers"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-13T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-13T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *       response=401,
     *       description="Unauthroized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Unauthroized",
     *       ref="#/components/responses/403"
     *     )
     * )
     */
    public function index()
    {
        $groups = Group::all();

        return ResponseHelper::success($groups, "List of all groups");
    }


    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/groups",
     *     tags={"Contacts"},
     *     summary="Create a new group",
     *     description="Creates a new contact group for the authenticated user.",
     *     operationId="createGroup",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="VIP Customers"),
     *             @OA\Property(property="description", type="string", example="High priority clients"),
     *             
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Group created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Group added successful"),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="VIP Customers"),
     *                 @OA\Property(property="slug", type="string", example="vip-customers"),
     *                 @OA\Property(property="description", type="string", example="High priority clients"),
     *                 @OA\Property(property="user_id", type="integer", example=5),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-13T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-13T12:00:00Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     ),
     *      @OA\Response(
     *       response=401,
     *       description="Unauthroized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Unauthroized",
     *       ref="#/components/responses/403"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'nullable|string'
        ]);
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields.", 422);
        }

        try {
            $data = [
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'description' => $request->description,
                'user_id' => auth()->user()->id
            ];

            $group = Group::create($data);

            return ResponseHelper::success($group, "Group added successful", 201);

        } catch (\Exception $e) {
            return ResponseHelper::error([], 'Error : ' . $e->getMessage());
        }
    }


    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/groups/{id}",
     *     tags={"Contacts"},
     *     summary="Get group details",
     *     description="Retrieve a contact group by its ID.",
     *     operationId="getGroupById",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the group to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Group retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Group details"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="VIP Customers"),
     *                 @OA\Property(property="slug", type="string", example="vip-customers"),
     *                 @OA\Property(property="description", type="string", example="High priority clients"),
     *                 @OA\Property(property="user_id", type="integer", example=5),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-13T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-13T12:00:00Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Group not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Group is not found."),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     ),
     *      @OA\Response(
     *       response=401,
     *       description="Unauthroized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Unauthroized",
     *       ref="#/components/responses/403"
     *     )
     * )
     */
    public function show(string $id)
    {
        $group = Group::find($id);

        if ($group) {
            return ResponseHelper::success($group, 'Group details', 200);
        }

        return ResponseHelper::error([], 'Group is not found.', 404);
    }


    /**
     * Update the specified resource in storage.
     */


    /**
     * @OA\Put(
     *     path="/groups/{id}",
     *     tags={"Contacts"},
     *     summary="Update a group",
     *     description="Updates a contact group by ID.",
     *     operationId="updateGroup",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the group to update",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="VIP Customers"),
     *             @OA\Property(property="description", type="string", example="High priority clients")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Group updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Group updated successfully"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="VIP Customers"),
     *                 @OA\Property(property="slug", type="string", example="vip-customers"),
     *                 @OA\Property(property="description", type="string", example="High priority clients"),
     *                 @OA\Property(property="user_id", type="integer", example=5),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-13T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-13T12:30:00Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Group not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Group not found."),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     ),
     *      @OA\Response(
     *       response=401,
     *       description="Unauthroized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Unauthroized",
     *       ref="#/components/responses/403"
     *     )
     * )
     */

    public function update(Request $request, $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return ResponseHelper::error([], "Group not found.", 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields.", 422);
        }

        try {
            $group->update([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'description' => $request->description
            ]);

            return ResponseHelper::success($group, "Group updated successfully", 200);

        } catch (\Exception $e) {
            return ResponseHelper::error([], 'Error: ' . $e->getMessage());
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     *     path="/groups/{id}",
     *     tags={"Contacts"},
     *     summary="Delete a group",
     *     description="Deletes a contact group by ID.",
     *     operationId="deleteGroup",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the group to delete",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Group deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Deleted successful"),
     *             @OA\Property(property="code", type="integer", example=204),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Group not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Group is not found."),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     ),
     *      @OA\Response(
     *       response=401,
     *       description="Unauthroized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Unauthroized",
     *       ref="#/components/responses/403"
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $group = Group::find($id);

        if ($group) {
            $group->delete();
            return ResponseHelper::success([], 'Deleted successful', 204);
        }

        return ResponseHelper::error([], 'Group is not found.', 404);
    }


}
