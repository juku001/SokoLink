<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupContact;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GroupContactController extends Controller
{


    /**
     * @OA\Post(
     *     path="/contact/group/assign",
     *     tags={"Contacts"},
     *     summary="Assign a contact to a group",
     *     description="Assigns a contact to a group for the authenticated user.",
     *     operationId="assignContactToGroup",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"group_id","contact_id"},
     *             @OA\Property(property="group_id", type="integer", example=1),
     *             @OA\Property(property="contact_id", type="integer", example=10)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Contact assigned to group successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Contact assigned to group."),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         ref="#/components/responses/403"
     *     ),
     *
     *     @OA\Response(
     *         response=409,
     *         description="Conflict: Contact already assigned",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Contact already assigned to this group"),
     *             @OA\Property(property="code", type="integer", example=409),
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
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:groups,id',
            'contact_id' => 'required|exists:contacts,id'
        ], [
            'group_id.exists' => 'Unknown group',
            'contact_id.exists' => 'Unknown contact'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields',
                422
            );
        }

        try {
            $authId = auth()->id();
            $data = $validator->validated();

            $group = Group::where('id', $data['group_id'])
                ->where('user_id', $authId)
                ->first();

            if (!$group) {
                return ResponseHelper::error([], "This group does not belong to you", 403);
            }

            $contact = Contact::where('id', $data['contact_id'])
                ->where('user_id', $authId)
                ->first();

            if (!$contact) {
                return ResponseHelper::error([], "This contact does not belong to you", 403);
            }
            $exists = GroupContact::where('group_id', $data['group_id'])
                ->where('contact_id', $data['contact_id'])
                ->exists();

            if ($exists) {
                return ResponseHelper::error([], "Contact already assigned to this group", 409);
            }

            $data['user_id'] = $authId;
            GroupContact::create($data);

            return ResponseHelper::success([], 'Contact assigned to group.', 201);

        } catch (Exception $e) {
            return ResponseHelper::error([], "Error: " . $e->getMessage(), 500);
        }
    }




    /**
     * @OA\Post(
     *     path="/contact/group/remove",
     *     tags={"Contacts"},
     *     summary="Remove a contact from a group",
     *     description="Removes the relationship between a contact and a group for the authenticated user.",
     *     operationId="removeContactFromGroup",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"group_id","contact_id"},
     *             @OA\Property(property="group_id", type="integer", example=1),
     *             @OA\Property(property="contact_id", type="integer", example=10)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Contact removed from group successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Contact removed from group."),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Relation not found (contact not in group)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="This contact is not assigned to the group."),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to validate fields"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         ref="#/components/responses/401"
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:groups,id',
            'contact_id' => 'required|exists:contacts,id'
        ], [
            'group_id.exists' => 'Unknown group',
            'contact_id.exists' => 'Unknown contact'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields',
                422
            );
        }

        try {
            $authId = auth()->id();
            $data = $validator->validated();

            // Check if relation exists for this user
            $relation = GroupContact::where('group_id', $data['group_id'])
                ->where('contact_id', $data['contact_id'])
                ->where('user_id', $authId) // ensure it belongs to the current user
                ->first();

            if (!$relation) {
                return ResponseHelper::error([], "This contact is not assigned to the group.", 404);
            }

            // Delete the pivot record
            $relation->delete();

            return ResponseHelper::success([], "Contact removed from group.", 200);

        } catch (Exception $e) {
            return ResponseHelper::error([], "Error: " . $e->getMessage(), 500);
        }
    }

}
