<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Helpers\SMSHelper;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Str;

class LogInController extends Controller
{



    /**
     * Request OTP for login using phone number.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Authentication"},
     *     summary="Request OTP login",
     *     description="Send an OTP code to the provided phone number for login.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="+255712345678")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Login successful. OTP sent.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Mobile login not enabled",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=403),
     *             @OA\Property(property="message", type="string", example="Mobile login not enabled for this user.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/422"
     *     )
     * )
     */
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
            SMSHelper::send($phoneNumber, "Your OTP is $otp");
            return ResponseHelper::success([], "Login successful. OTP sent.");

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], $e->getMessage(), 500);
        }
    }


    public function resendOtp(){
        
    }



    /**
     * Verify OTP and log in the user.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *     path="/auth/verify/otp",
     *     tags={"Authentication"},
     *     summary="Verify OTP for login",
     *     description="Validates the OTP and issues an access token for the user.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone","otp"},
     *             @OA\Property(property="phone", type="string", example="+255712345678"),
     *             @OA\Property(property="otp", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP verified successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="OTP verified. Login successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string", example="1|eyJhbGciOiJI..."),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="phone", type="string", example="+255712345678"),
     *                     @OA\Property(property="role", type="string", example="seller"),
     *                     @OA\Property(property="phone_verified_at", type="string", example="2025-09-11T14:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired OTP",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Invalid or expired OTP.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/422"
     *     )
     * )
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^\+255\d{9}$/',
            'otp' => 'required|string'
        ], [
            'phone.regex' => 'Phone number format is +255XXXXXXXXX'
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

        if (is_null($user->phone_verified_at)) {
            $user->update(['phone_verified_at' => now()]);
        }

        DB::table('otps')->where('phone', $request->phone)->delete();

        $token = $user->createToken('mobile-login')->plainTextToken;

        $user->update(['last_login_at' => now()]);


        return ResponseHelper::success([
            'token' => $token,
            'user' => $user
        ], "OTP verified. Login successful.");
    }


    /**
     * Login with email and password.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *     path="/auth/login/email",
     *     tags={"Authentication"},
     *     summary="Login with email",
     *     description="Authenticates a user using email and password, returning an access token on success.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User login successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string", example="1|eyJhbGciOiJI..."),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="user@example.com"),
     *                     @OA\Property(property="phone", type="string", example="+255712345678"),
     *                     @OA\Property(property="role", type="string", example="seller")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Account not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Account does not exist.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/422"
     *     )
     * )
     */
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
            return ResponseHelper::error([], 'Account does not exist.', 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return ResponseHelper::error([], 'Invalid account credentials.', 401);
        }

        $token = $user->createToken('email-login')->plainTextToken;
        $user->update(['last_login_at' => now()]);
        return ResponseHelper::success([
            'token' => $token,
            'user' => $user
        ], 'User login successful.');
    }










}
