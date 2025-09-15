<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Validator;

class LogInProviderController extends Controller
{

    /**
     * Add or enable a new authentication provider for the authenticated user.
     *
     * @OA\Post(
     *     path="/auth/provider/add",
     *     tags={"Authentication"},
     *     summary="Add or enable auth provider",
     *     description="Allows a user to enable an authentication provider such as email, Google, or mobile for login.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="provider",
     *                 type="string",
     *                 description="The authentication provider to enable",
     *                 enum={"email","google","mobile"},
     *                 example="mobile"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authentication provider set successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Auth Provider set"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="provider", type="string", example="mobile"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="user_id", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation or precondition failed",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Please set a phone number before enabling mobile login.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         ref="#/components/responses/422"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */

    public function store(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|in:email,google,mobile',
        ], [
            'provider.in' => 'Choose from mobile,google or email',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields', 422);
        }
        DB::beginTransaction();
        try {

            $provider = $request->provider;

            if ($provider === 'email' || $provider === 'google') {
                if ($user->email == null) {
                    return ResponseHelper::error([], 'Please set an email address before enabling email login.', 400);
                }

                if (!$user->password) {
                    $password = str()->random(10);
                    $user->update(['password' => bcrypt($password)]);
                }

                //send email for the password if provider is email 
            }

            if ($provider === 'mobile' && !$user->phone) {
                return ResponseHelper::error([], "Please set a phone number before enabling mobile login.", 400);
            }

            $authProvider = $user->providers()
                ->updateOrCreate(
                    ['provider' => $provider],
                    ['is_active' => true]
                );

            DB::commit();
            return ResponseHelper::success($authProvider, "Auth Proivder set");
        } catch (QueryException $e) {
            DB::rollBack();
            return ResponseHelper::error([], "DB Error : " . $e->getMessage(), 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], "Error : " . $e->getMessage(), 500);
        }
    }




    /**
     * Remove or disable an authentication provider for the authenticated user.
     *
     * @OA\Post(
     *     path="/auth/provider/remove",
     *     tags={"Authentication"},
     *     summary="Remove auth provider",
     *     description="Allows a user to remove an authentication provider such as email, Google, or mobile. Ensures at least one provider remains active.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="provider",
     *                 type="string",
     *                 description="The authentication provider to remove",
     *                 enum={"email","google","mobile"},
     *                 example="mobile"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authentication provider removed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Auth provider removed"),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation or business rule failed",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="You must have at least one authentication method enabled.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         ref="#/components/responses/422"
     *     )
     * )
     */

    public function destroy(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|in:email,google,mobile',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields.", 422);
        }

        $provider = $request->provider;

        $activeProviders = $user->providers()->where('is_active', true)->count();

        if ($activeProviders <= 1) {
            return ResponseHelper::error([], "You must have at least one authentication method enabled.", 400);
        }

        if ($activeProviders == 2 && $provider !== 'google') {
            $other = $user->providers()
                ->where('is_active', true)
                ->where('provider', '!=', $provider)
                ->first();

            if ($other->provider === 'google') {
                return ResponseHelper::error([], "Google cannot be the only authentication method.", 400);
            }
        }

        $user->providers()->where('provider', $provider)->delete();

        return ResponseHelper::success([], "Auth provider removed");
    }




    /**
     * Change the status of an authentication provider for the authenticated user.
     *
     * @OA\Post(
     *     path="/auth/provider/change",
     *     tags={"Authentication"},
     *     summary="Change auth provider status",
     *     description="Activate or deactivate an authentication provider such as email, Google, or mobile. Ensures at least one provider remains active.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="provider",
     *                 type="string",
     *                 description="The authentication provider to update",
     *                 enum={"email","google","mobile"},
     *                 example="mobile"
     *             ),
     *             @OA\Property(
     *                 property="is_active",
     *                 type="boolean",
     *                 description="Set to true to activate, false to deactivate",
     *                 example=true
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authentication provider updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Auth provider updated"),
     *             @OA\Property(property="data", type="string", example="mobile")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Business rule violated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="You must keep at least one authentication method active.")
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
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|in:email,google,mobile',
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields.", 422);
        }

        $provider = $request->provider;
        $isActive = $request->is_active;

        if ($isActive === false) {
            $activeProviders = $user->providers()->where('is_active', true)->count();
            if ($activeProviders <= 1) {
                return ResponseHelper::error([], "You must keep at least one authentication method active.", 400);
            }
        }

        $authProvider = $user->providers()
            ->where('provider', $provider)
            ->firstOrFail();

        $authProvider->update(['is_active' => $isActive]);

        return ResponseHelper::success($provider, "Auth provider updated");
    }

}
