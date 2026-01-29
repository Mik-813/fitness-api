<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Rules\Recaptcha;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(Request $request): JsonResponse
    {
        $request->validate([
            'recaptcha_token' => ['required', new Recaptcha],
        ]);

        return response()->json([
            'url' => Socialite::driver('google')->stateless()->redirect()->getTargetUrl(),
        ]);
    }

    public function callback(): JsonResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid credentials provided.'], 422);
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            if (!$user->google_id) {
                $user->google_id = $googleUser->getId();
                $user->save();
            }
        } else {
            $user = new User();
            $user->email = $googleUser->getEmail();
            $user->google_id = $googleUser->getId();
            $user->password = bcrypt(Str::random(32));
            $user->email_verified_at = now();
            $user->save();
        }

        return response()->json([
            'token' => 'Bearer ' . $user->createToken('google-auth')->plainTextToken,
        ]);
    }
}