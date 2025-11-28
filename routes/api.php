<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
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

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/my-purchases', [ProductController::class, 'myPurchases']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::post('/like/{id}', [ProductController::class, 'like']);
        Route::post('/purchase/{id}', [ProductController::class, 'purchase']);
    });

    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{id}', [CategoryController::class, 'show']);
    });
});
