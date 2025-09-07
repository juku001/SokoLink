<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GoogleSignInController extends Controller
{
    public function login(Request $request)
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::firstOrCreate(
            ['email' => $googleUser->getEmail()], // search by email
            [
                'name' => $googleUser->user['name'] ?? '',
                'google_id' => $googleUser->getId(),
                'role' => 'seller',
                'email_verified_at' => Carbon::now()
            ]
        );

        if (!$user->google_id) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'profile_pic' => $googleUser->getAvatar(),
            ]);
        }

        $token = $user->createToken('google-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
}
