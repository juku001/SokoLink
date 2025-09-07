<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Validator;

class LogInProviderController extends Controller
{
    //adding a new authprovider
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
