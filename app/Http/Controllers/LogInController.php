<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Str;

class LogInController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^\+255\d{9}$/'
        ], [
            'phone.regex' => 'Phone number format is +255XXXXXXXXX'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields.", 422);
        }

        DB::beginTransaction();
        try {
            $otp = random_int(100000, 999999);
            $phoneNumber = $request->phone;

            $user = User::firstOrCreate(['phone' => $phoneNumber]);

            if ($user->wasRecentlyCreated) {
                $user->providers()->create([
                    'provider' => 'mobile',
                    'is_active' => true,
                ]);
            } else {
                $hasMobile = $user->providers()
                    ->where('provider', 'mobile')
                    ->where('is_active', true)
                    ->exists();

                if (!$hasMobile) {
                    return ResponseHelper::error([], "Mobile login not enabled for this user.", 403);
                }
            }


            DB::table('otps')->updateOrInsert(
                ['phone' => $phoneNumber],
                [
                    'otp' => $otp,
                    'expires_at' => now()->addMinutes(2),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::commit();
            // 🔑 Send OTP via SMS gateway here instead of returning it
            // e.g. SmsService::send($phoneNumber, "Your OTP is $otp");

            return ResponseHelper::success([], "Login successful. OTP Sent.");

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], $e->getMessage(), 0);
        }
    }



    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^\+255\d{9}$/',
            'otp' => 'required|string'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields.", 422);
        }

        $record = DB::table('otps')
            ->where('phone', $request->phone)
            ->where('otp', $request->otp)
            ->where('expires_at', '>=', now())
            ->first();

        if (!$record) {
            return ResponseHelper::error([], "Invalid or expired OTP.", 400);
        }

        $user = User::where('phone', $request->phone)->firstOrFail();
        if ($user->phone_verified_at == null) {
            $user->phone_verified_at = now();
            $user->save();
        }

        DB::table('otps')->where('phone', $request->phone)->delete();

        $token = $user->createToken('mobile-login')->plainTextToken;

        return ResponseHelper::success([
            'token' => $token,
            'user' => $user
        ], "OTP verified. Login successful.");
    }


    public function email(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Validation Error.',
                422
            );
        }
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return
                ResponseHelper::error([], 'Account does not exist.', 404);
        }
        if (!Hash::check($request->password, $user->password)) {
            return ResponseHelper::error(
                [],
                'Invalid account credentials.',
                401
            );
        }
        $success['token'] = $user->createToken('TayariToken')->plainTextToken;
        $success['user'] = $user;
        return ResponseHelper::success($success, 'User login successful.');
    }



    public function destroy(Request $request)
    {
        $authId = $request->user()->id;

        $request->user()->currentAccessToken()->delete();

        return ResponseHelper::success(
            [],
            "Logged out successful."
        );
    }


}
