<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{


    /**
     * Get a list of all users except super admins.
     *
     * @OA\Get(
     *     path="/users",
     *     tags={"Users"},
     *     summary="List all users",
     *     description="Retrieve a list of all users excluding super admins.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Users retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/User")
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
        $users = User::where('role', '!=', 'super_admin')->get();

        return ResponseHelper::success($users, "Users retrieved successfully");
    }




    /**
     * Get the authenticated user's details.
     *
     * @OA\Get(
     *     path="/auth/me",
     *     tags={"Users"},
     *     summary="Get authenticated user details",
     *     description="Returns the details of the currently authenticated user based on their API token.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User Details"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/components/schemas/User"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */


    public function show()
    {
        $user = auth()->user();

        return ResponseHelper::success($user, "User Details");
    }



    /**
     * Update the status of a specific user.
     *
     * @OA\Patch(
     *     path="/users/{id}",
     *     tags={"Users"},
     *     summary="Update user status",
     *     description="This is for the Admin to change the status of a specific user (active, inactive, suspended).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", enum={"active","inactive","suspended"}, example="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User status updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User status updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *        ref="#/components/responses/422"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */

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




    /**
     * @OA\Put(
     *     path="/profile/update",
     *     summary="Update authenticated user's profile",
     *     description="Updates the name, email, phone, and/or profile picture of the logged-in user.",
     *     operationId="profileUpdate",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="name", type="string", maxLength=255, example="Jane Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *                 @OA\Property(property="phone", type="string", maxLength=20, example="+255700000000"),
     *                 @OA\Property(
     *                     property="profile_pic",
     *                     type="string",
     *                     format="binary",
     *                     description="JPEG/PNG/GIF/WEBP image, max 2 MB"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=12),
     *                 @OA\Property(property="name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="email", type="string", example="jane@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+255700000000"),
     *                 @OA\Property(property="profile_pic", type="string", example="storage/profile_pics/1694783948_avatar.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/422"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         ref="#/components/responses/401"
     *     )
     * )
     */
    public function profileUpdate(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|required|string|max:20',
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields', 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('profile_pic')) {
            $file = $request->file('profile_pic');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('profile_pics', $filename, 'public'); // storage/app/public/profile_pics
            $data['profile_pic'] = $path;
        }

        $user->update($data);

        return ResponseHelper::success($user, 'Profile updated successfully');
    }



    /**
     * @OA\Put(
     *     path="/admin/users/{id}/profile",
     *     summary="Update a user's profile (Admin)",
     *     description="Allows an administrator to update any user's name, email, phone, or profile picture.",
     *     operationId="profileUpdateByAdmin",
     *     tags={"Admin"},
     *     security={{"sanctum":{}}},   
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the user to update",
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="name", type="string", maxLength=255, example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                 @OA\Property(property="phone", type="string", maxLength=20, example="+255700000000"),
     *                 @OA\Property(
     *                     property="profile_pic",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional image file (jpeg, png, jpg, gif, webp), max 2 MB"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=42),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+255700000000"),
     *                 @OA\Property(property="profile_pic", type="string", example="storage/profile_pics/1694783948_avatar.jpg")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found."),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/422"
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         ref="#/components/responses/401"
     *     )
     * )
     */

    public function profileUpdateByAdmin(Request $request, $id)
    {

        $user = User::find($id);
        if (!$user) {
            return ResponseHelper::error([], 'User not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|required|string|max:20',
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields', 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('profile_pic')) {
            $file = $request->file('profile_pic');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('profile_pics', $filename, 'public'); // storage/app/public/profile_pics
            $data['profile_pic'] = $path;
        }

        $user->update($data);

        return ResponseHelper::success($user, 'Profile updated successfully');
    }

}
