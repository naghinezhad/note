<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

Route::post('/login', [AuthController::class, 'login']);

Route::post('/request-otp-login', [AuthController::class, 'requestOtpLogin']);
Route::post('/login-with-otp', [AuthController::class, 'loginWithOtp']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-forgot-password-otp', [AuthController::class, 'verifyForgotPasswordOtp']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [ProfileController::class, 'profile']);

    Route::post('/change-password', [ProfileController::class, 'changePassword']);

    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});
