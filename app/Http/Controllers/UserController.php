<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{


    public function index(Request $request)
    {
        $users = User::where('role', '!=', 'super_admin')->get();

        return ResponseHelper::success($users, "Users retrieved successfully");
    }




    public function show()
    {
        $user = auth()->user();

        return ResponseHelper::success($user, "User Details");
    }


    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields.", 422);
        }

        $user->update([
            'status' => $request->status
        ]);

        return ResponseHelper::success($user, "User status updated successfully");
    }

}
