<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Rules\Recaptcha;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'recaptcha_token' => ['required', new Recaptcha],
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'user' => $user,
            'token' => 'Bearer ' . $user->createToken('auth_token')->plainTextToken,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'recaptcha_token' => ['required', new Recaptcha],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'user' => $user,
            'token' => 'Bearer ' . $user->createToken('auth_token')->plainTextToken,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function sendVerificationEmail(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $user = $request->user();
        $token = Str::random(64);

        DB::table('email_verification_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $token, 'created_at' => now()]
        );

        $url = $request->url . (str_contains($request->url, '?') ? '&' : '?') . 'token=' . $token;

        Mail::raw("Please verify your email by clicking this link: {$url}", function ($message) use ($user) {
            $message->to($user->email)->subject('Verify Your Email');
        });

        return response()->json(['message' => 'Verification email sent']);
    }

    public function verify(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $record = DB::table('email_verification_tokens')->where('token', $request->token)->first();

        if (! $record) {
            return response()->json(['message' => 'Invalid or expired token'], 422);
        }

        $user = User::where('email', $record->email)->first();
        if ($user) {
            $user->email_verified_at = now();
            $user->save();
        }

        DB::table('email_verification_tokens')->where('email', $record->email)->delete();

        return response()->json(['message' => 'Email verified successfully']);
    }

    public function sendResetPasswordEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'url' => 'required|url',
        ]);

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => now()]
        );

        $url = $request->url . (str_contains($request->url, '?') ? '&' : '?') . 'token=' . $token;

        Mail::raw("Reset your password by clicking this link: {$url}", function ($message) use ($request) {
            $message->to($request->email)->subject('Reset Your Password');
        });

        return response()->json(['message' => 'Password reset email sent']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|min:8',
        ]);

        $record = DB::table('password_reset_tokens')->where('token', $request->token)->first();

        if (! $record) {
            return response()->json(['message' => 'Invalid or expired token'], 422);
        }

        $user = User::where('email', $record->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $record->email)->delete();

        return response()->json(['message' => 'Password reset successfully']);
    }
}