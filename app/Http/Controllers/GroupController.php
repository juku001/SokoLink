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
    public function index()
    {

        $groups = Group::all();

        return ResponseHelper::success($groups, "List of all groups");
    }

    /**
     * Store a newly created resource in storage.
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
