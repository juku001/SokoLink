<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Seller;
use App\Models\User;
use App\Services\AdminLogService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{




    public function unauthorized()
    {
        return ResponseHelper::error(
            [],
            'Unauthorized',
            401
        );
    }




    /**
     * @OA\Get(
     *   tags={"Authentication"},
     *   path="/is_auth",
     *   summary="This API is used to check if the user is authenticated or not. It returns status false if not authenticated.",
     *   @OA\Response(
     *     response=200, 
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="status", type="boolean", example=true),
     *       @OA\Property(property="code", type="integer", example=200),
     *       @OA\Property(property="message", type="string", example="Authenticated"),
     *     )
     *   ),
     *   @OA\Response(
     *     response=401, 
     *     description="Unauthorized",
     *     ref="#/components/responses/401"
     *   ),
     * )
     */

    public function authorized()
    {
        return ResponseHelper::success(
            [],
            'Authenticated',
            200
        );
    }


    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Logout user",
     *     description="Revokes the currently authenticated user's access token and logs them out.",
     *     operationId="logoutUser",
     *     tags={"Authentication"},
     *
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="User logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out successful."),
     *             @OA\Property(property="code", type="integer", example=200),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - No valid token provided",
     *         ref="#/components/responses/401"
     *     )
     * )
     */
    public function destroy(Request $request)
    {
        $authId = $request->user()->id;


        $request->user()->currentAccessToken()->delete();

        return ResponseHelper::success(
            [],
            "Logged out successful."
        );
    }








    /**
     * @OA\Post(
     *   path="/auth/verified/email",
     *   tags={"Authentication"},
     *   summary="Check if a user's email is verified",
     *   operationId="isVerifiedEmail",
     *   description="Validates the provided email and returns whether the user has a non-null email_verified_at.",
     *   requestBody={
     *     "required": true,
     *     "content": {
     *       "application/json": {
     *         "schema": {
     *           "type": "object",
     *           "required": {"email"},
     *           "properties": {
     *             "email": {
     *               "type": "string",
     *               "format": "email",
     *               "example": "user@example.com",
     *               "description": "User's email address"
     *             }
     *           }
     *         }
     *       }
     *     }
     *   },
     *     @OA\Response(
     *         response=200,
     *         description="User verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User verified."),
     *             @OA\Property(property="code", type="integer", example=200),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User can't be found."),
     *             @OA\Property(property="code", type="integer", example=404),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not verified."),
     *             @OA\Property(property="code", type="integer", example=400),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *       response=422,
     *       description="Unprocessed Content",
     *       ref="#/components/responses/422"
     *     )
     * )
     */

    public function verifiedEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email'
        ]);
        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate',
                422
            );
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return ResponseHelper::error(
                [],
                "User can't be found",
                404
            );
        }

        if ($user->email_verified_at == null) {
            return ResponseHelper::error(
                [],
                'User email address not verified',
                400
            );
        }
        return ResponseHelper::success([], 'User verified');
    }






    /**
     * @OA\Post(
     *   path="/auth/verified/mobile",
     *   tags={"Authentication"},
     *   summary="Check if a user's mobile number is verified",
     *   operationId="isVerifiedMobile",
     *   description="Validates the provided mobile and returns whether the user has a non-null phone_verified_at.",
     *   requestBody={
     *     "required": true,
     *     "content": {
     *       "application/json": {
     *         "schema": {
     *           "type": "object",
     *           "required": {"mobile"},
     *           "properties": {
     *             "mobile": {
     *               "type": "string",
     *               "example": "+255712345678",
     *               "description": "User's phone number"
     *             }
     *           }
     *         }
     *       }
     *     }
     *   },
     *     @OA\Response(
     *         response=200,
     *         description="User verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User verified."),
     *             @OA\Property(property="code", type="integer", example=200),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User can't be found."),
     *             @OA\Property(property="code", type="integer", example=404),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not verified."),
     *             @OA\Property(property="code", type="integer", example=400),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *       response=422,
     *       description="Unprocessed Content",
     *       ref="#/components/responses/422"
     *     )
     * )
     */
    public function verifiedMobile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string|regex:/^\+255\d{9}$/'
        ]);
        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate',
                422
            );
        }

        $user = User::where('phone', $request->mobile)->first();
        if (!$user) {
            return ResponseHelper::error(
                [],
                "User can't be found",
                404
            );
        }

        if ($user->phone_verified_at == null) {
            return ResponseHelper::error(
                [],
                'User phone number not verified',
                400
            );
        }
        return ResponseHelper::success([], 'User verified');
    }


    /**
     * Upgrade user account to seller.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *     path="/auth/be/seller",
     *     tags={"Users"},
     *     summary="Convert buyer account to seller",
     *     description="Allows an authenticated user to become a seller by providing payout account details and payout method.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payout_account","payout_method"},
     *             @OA\Property(property="payout_account", type="string", example="255XXXXXXXXX"),
     *             @OA\Property(property="payout_method", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account set to seller successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Account set to seller."),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/422"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */
    public function seller(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payout_account' => 'required|string',
            'payout_method' => 'required|numeric|exists:payment_methods,id',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields", 422);
        }

        try {
            $authId = auth()->id();
            $user = User::findOrFail($authId);

            if ($user->role === 'seller') {
                return ResponseHelper::error([], "User is already a seller.", 400);
            }

            $user->update(['role' => 'seller']);

            Seller::updateOrCreate(
                ['user_id' => $authId],
                [
                    'payout_account' => $request->payout_account,
                    'payout_method' => $request->payout_method
                ]
            );

            return ResponseHelper::success([], "Account set to seller.");

        } catch (QueryException $e) {
            return ResponseHelper::error([], "DB Error: " . $e->getMessage(), 400);
        } catch (Exception $e) {
            return ResponseHelper::error([], "Error: " . $e->getMessage(), 500);
        }
    }




    /**
     * @OA\Put(
     *     path="/auth/be/seller",
     *     summary="Update seller payout details",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payout_account","payout_method"},
     *             @OA\Property(property="payout_account", type="string", example="mpesa-254700123456"),
     *             @OA\Property(property="payout_method", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Seller details updated successfully",
     *         @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="status", type="boolean", example=true),
     *        @OA\Property(property="message", type="string", example="Seller details updated."),
     *        @OA\Property(property="code", type="integer", example=200),
     *      )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Not a seller or database error",
     *         @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="status", type="boolean", example=false),
     *        @OA\Property(property="message", type="string", example="Not a seller or database error."),
     *        @OA\Property(property="code", type="integer", example=400),
     *      )
     *         
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/422"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         ref="#/components/responses/500"
     *     )
     * )
     */
    public function updateSeller(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payout_account' => 'required|string',
            'payout_method' => 'required|numeric|exists:payment_methods,id',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields", 422);
        }

        try {
            $authId = auth()->id();
            $seller = Seller::where('user_id', $authId)->first();

            if (!$seller) {
                return ResponseHelper::error([], "You are not registered as a seller.", 400);
            }

            $seller->update([
                'payout_account' => $request->payout_account,
                'payout_method' => $request->payout_method,
            ]);

            return ResponseHelper::success($seller, "Seller payout details updated.");

        } catch (QueryException $e) {
            return ResponseHelper::error([], "DB Error: " . $e->getMessage(), 400);
        } catch (Exception $e) {
            return ResponseHelper::error([], "Error: " . $e->getMessage(), 500);
        }
    }


}
