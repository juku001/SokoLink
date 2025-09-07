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
