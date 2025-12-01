<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Mail;

class PasswordController extends Controller
{



    /**
     * Send password reset OTP to user email.
     *
     * @OA\Post(
     *     path="/auth/password/forgot",
     *     tags={"Authentication"},
     *     summary="Request password reset OTP",
     *     description="Generates a one-time password (OTP) and sends it to the user's registered email for password reset.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OTP sent to your email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Failed to validate fields."),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Email not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="User with this email does not exist")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields.", 422);
        }

        $otp = random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $otp,
                'created_at' => now(),
            ]
        );

        Mail::raw("Your password reset OTP is: $otp", function ($msg) use ($request) {
            $msg->to($request->email)->subject('Password Reset OTP');
        });

        return ResponseHelper::success([], "OTP sent to your email");
    }



    /**
     * Reset user password using OTP.
     *
     * @OA\Post(
     *     path="/auth/password/reset",
     *     tags={"Authentication"},
     *     summary="Reset password with OTP",
     *     description="Allows a user to reset their password by providing a valid OTP sent to their email.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="otp", type="string", example="123456"),
     *             @OA\Property(property="password", type="string", format="password", example="NewPassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="NewPassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Password has been reset successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired OTP",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Invalid OTP")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         ref="#/components/responses/422"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:users,email',
            'otp' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields.", 422);
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->otp)
            ->first();

        if (!$record) {
            return ResponseHelper::error([], "Invalid OTP", 400);
        }

        if (now()->diffInMinutes($record->created_at) > 15) {
            return ResponseHelper::error([], "OTP has expired", 400);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return ResponseHelper::success([], "Password has been reset successfully");
    }












    /**
     * Update user password.
     *
     * @OA\Post(
     *     path="/auth/password/update",
     *     tags={"Authentication"},
     *     summary="Update password for authenticated user",
     *     description="Allows an authenticated user to change their password by providing the current password and a new password.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_password", type="string", format="password", example="CurrentPass123"),
     *             @OA\Property(property="password", type="string", format="password", example="NewPass123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="NewPass123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Password updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Current password is incorrect",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Current password is incorrect")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         ref="#/components/responses/422"
     *     )
     * )
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'Password confirmation does not match.'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields.", 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return ResponseHelper::error([], "Current password is incorrect", 400);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return ResponseHelper::success([], 'Password updated successfully');
    }




}
