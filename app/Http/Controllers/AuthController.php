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
     *   path="/is_verified",
     *   tags={"Authentication"},
     *   summary="Check if a user's email is verified",
     *   operationId="isVerified",
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
     *     )
     * )
     */

    public function verified(Request $request)
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
                'User not verified',
                400
            );
        }
        return ResponseHelper::success([], 'User verified');
    }


    public function seller(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payout_account' => 'required',
            'payout_method' => 'required|numeric|exists:payment_methods,id'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields", 422);
        }

        try {

            $authId = auth()->user()->id;
            $user = User::find($authId);

            $user->role = 'seller';
            if ($user->save()) {
                Seller::create([
                    'user_id'=> $authId,
                    'payout_account' => $request->payout_account,
                    'payout_method' => $request->payout_method
                ]);
            }

            return ResponseHelper::success([], "Account set to seller.");

        } catch (QueryException $e) {
            return ResponseHelper::error(
                [],
                "DB Error : " . $e->getMessage(),
                400
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                [],
                "Error : " . $e->getMessage(),
                500
            );
        }

    }
}
