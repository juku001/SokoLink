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
    public function index(Request $request)
    {

        $token = $request->bearerToken();
        $authUser = null;
        if ($token != null) {
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
            'phone.regex' => 'Phone number format is +255XXXXXXXXX'
        ];

        $role = 'admin';
        $password = Str::random(9);

        if (!$authUser) {
            $role = 'seller';
            $rules['password'] = 'required|string';
            $password = $request->password;
        }
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields',
                422
            );
        }

        DB::beginTransaction();
        try {


            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => $role,
                'password' => $password
            ];

            $user = User::create($data);
            CustomFunctions::createProviders($user->id, AuthProviderType::Email);
            DB::commit();
            return ResponseHelper::success([], ucfirst($role) . "  registered successful.");
        } catch (QueryException $e) {
            DB::rollBack();
            return ResponseHelper::error([], "DB Error : $e", 400);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], "Server Error : $e", 500);
        }


    }

    public function verify(Request $request)
    {

    }
}
