<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleSignInController extends Controller
{

    /**
     * @OA\Post(
     *     path="/auth/login/google",
     *     summary="Redirect to Google OAuth",
     *     description="Starts the Google OAuth flow by redirecting the user to Google's login/consent screen.",
     *     operationId="googleRedirect",
     *     tags={"Authentication"},
     *
     *     @OA\Response(
     *         response=302,
     *         description="Redirect response to Google login page",
     *         @OA\JsonContent(
     *             @OA\Property(property="redirect_url", type="string", example="https://accounts.google.com/o/oauth2/auth?..."),
     *         )
     *     )
     * )
     */
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
        $user->update(['last_login_at' => now()]);
        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
}
