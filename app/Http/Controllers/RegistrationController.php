<?php

namespace App\Http\Controllers;

use App\Helpers\AuthProviderType;
use App\Helpers\CustomFunctions;
use App\Helpers\ResponseHelper;
use App\Models\User;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class RegistrationController extends Controller
{



    /**
     * Register a new user.
     *
     * - If authenticated (Bearer token provided), registers an **admin**.
     * - If not authenticated, registers a **seller**.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *     path="/auth/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     description="Registers a new user as seller (if unauthenticated) or admin (if called by an authenticated user).",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","phone"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
     *             @OA\Property(property="phone", type="string", example="+255712345678"),
     *             @OA\Property(property="password", type="string", example="secret123", description="Required only when registering seller")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Seller registered successful.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Database error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="DB Error: ...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $token = $request->bearerToken();
        $authUser = null;

        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $authUser = $accessToken->tokenable;
            }
        }

        $rules = [
            'email' => 'required|string|email|unique:users,email',
            'name' => 'required|string',
            'phone' => [
                'required',
                'string',
                'unique:users,phone',
                'regex:/^\+255\d{9}$/'
            ],
        ];

        $messages = [
            'phone.regex' => 'Phone number format is +255XXXXXXXXX',
        ];

        $role = 'admin';
        $password = Str::random(9);

        if (!$authUser) {
            $role = 'seller';
            $rules['password'] = 'required|string|min:6';
            $password = $request->password;
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields', 422);
        }

        DB::beginTransaction();
        try {
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => $role,
                'password' => bcrypt($password),
            ];

            $user = User::create($data);

            CustomFunctions::createProviders($user->id, AuthProviderType::Email);

            DB::commit();
            return ResponseHelper::success([], ucfirst($role) . " registered successful.");

        } catch (QueryException $e) {
            DB::rollBack();
            return ResponseHelper::error([], "DB Error : " . $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], "Server Error : " . $e->getMessage(), 500);
        }
    }







    /**
     * @OA\Post(
     *     path="/auth/verify/email",
     *     tags={"Authentication"},
     *     summary="Send email verification link",
     *     description="Sends a verification link to the authenticated user's email address.",
     *     operationId="sendEmailVerification",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Verification link sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Verification link sent to your email"),
     *             @OA\Property(property="code", type="integer", example=200),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *           @OA\Property(property="status", type="boolean", example=false),
     *           @OA\Property(property="message", type="string", example="Email already verified"),
     *           @OA\Property(property="code", type="integer", example=400),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         ref="#/components/responses/401"
     *     )
     * )
     */
    public function verify()
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return ResponseHelper::error([], 'Email already verified', 400);
        }

        $user->sendEmailVerificationNotification();

        return ResponseHelper::success([], 'Verification link sent to your email');
    }





}
