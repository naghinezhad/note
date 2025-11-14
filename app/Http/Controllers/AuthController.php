<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'مشکل در ثبت‌نام کاربر.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $this->sendOtp($user->email, 'ثبت‌نام');

        return response()->json(['message' => 'ثبت‌نام با موفقیت انجام شد. کد تأیید برای شما ارسال شد.']);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'مشکل در احراز هویت کاربر.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $otp = Otp::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (! $otp) {
            return response()->json(['message' => 'کد وارد شده صحیح نیست یا مهلت استفاده از کد تأیید به پایان رسیده است.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->markEmailAsVerified();
        $user->save();

        $otp->delete();

        return response()->json(['message' => 'ثبت‌نام با موفقیت انجام شد.']);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'مشکل در احراز هویت کاربر.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password) || ! $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'ایمیل یا رمز عبور اشتباه است.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['message' => 'ورود با موفقیت انجام شد.', 'token' => $token]);
    }

    public function requestOtpLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'مشکل در احراز هویت کاربر.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['message' => 'ایمیل نامعتبر است.'], 400);
        }

        $this->sendOtp($user->email, 'ورود');

        return response()->json(['message' => 'کد ورود برای شما ارسال شد.']);
    }

    public function loginWithOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'مشکل در احراز هویت کاربر.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $otp = Otp::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (! $otp) {
            return response()->json(['message' => 'کد تأیید نامعتبر است.'], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $user->save();
        }

        $token = $user->createToken('api-token')->plainTextToken;

        $otp->delete();

        return response()->json(['message' => 'ورود با کد تأیید با موفقیت انجام شد.', 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'خروج از حساب با موفقیت انجام شد.']);
    }

    private function sendOtp($email, $type)
    {
        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Carbon::now()->addMinutes(10);

        Otp::create([
            'email' => $email,
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);

        $subject = $type === 'ثبت‌نام' ? 'کد تأیید ثبت‌نام' : 'کد تأیید ورود';
        Mail::raw("کد OTP شما: $code", function ($message) use ($email, $subject) {
            $message->to($email)->subject($subject);
        });
    }
}
