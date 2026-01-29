<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SocialAuthController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify', [AuthController::class, 'verify']);
    Route::post('/email/send-reset-password', [AuthController::class, 'sendResetPasswordEmail']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::post('/google/redirect', [SocialAuthController::class, 'redirect']);
    Route::get('/google/callback', [SocialAuthController::class, 'callback']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/email/send-verification', [AuthController::class, 'sendVerificationEmail']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/stats', function () {
        return response()->json(['message' => 'Admin statistics dashboard']);
    });
});

Route::fallback(function () {
    return response()->json(['message' => 'Not Found'], 404);
});
