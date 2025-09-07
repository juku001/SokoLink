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
