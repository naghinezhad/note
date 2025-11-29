<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/register",
     *     summary="ثبت‌نام کاربر جدید",
     *     description="ثبت‌نام کاربر با ایمیل و رمز عبور و ارسال کد",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="ایمیل کاربر"),
     *             @OA\Property(property="password", type="string", format="password", minLength=6, example="123456", description="رمز عبور (حداقل 6 کاراکتر)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="ثبت‌نام موفق",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="ثبت‌نام کاربر با موفقیت انجام شد. کد تأیید برای شما ارسال شد.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="مشکل در ثبت‌نام کاربر."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The email has already been taken.")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="password",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The password must be at least 6 characters.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $messages = [
            'email.required' => 'لطفاً ایمیل خود را وارد کنید.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'password.required' => 'لطفاً رمز عبور خود را وارد کنید.',
            'password.min' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
        ];

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ], $messages);

        if ($validator->fails()) {

            $firstError = $validator->errors()->first();

            return response()->json([
                'message' => $firstError,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $this->sendOtp($user->email, 'ثبت‌نام');

        return response()->json(['message' => 'ثبت‌نام با موفقیت انجام شد. کد تأیید ارسال شد.']);
    }

    /**
     * @OA\Post(
     *     path="/verify-otp",
     *     summary="تأیید کد",
     *     description="تأیید ایمیل کاربر با استفاده از کد و دریافت توکن احراز هویت",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","code"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="ایمیل کاربر"),
     *             @OA\Property(property="code", type="string", example="123456", description="کد 6 رقمی OTP")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="تأیید موفق",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="ثبت‌نام کاربر با موفقیت انجام شد."),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="token", type="string", example="1|randomtokenstring...")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="کد نامعتبر یا منقضی شده",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="کد تأیید نامعتبر است.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="مشکل در احراز هویت کاربر."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $messages = [
            'email.required' => 'لطفاً ایمیل را وارد کنید.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
            'code.required' => 'لطفاً کد تأیید را وارد کنید.',
            'code.string' => 'کد تأیید باید به صورت متن باشد.',
        ];

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string',
        ], $messages);

        if ($validator->fails()) {

            $firstError = $validator->errors()->first();

            return response()->json([
                'message' => $firstError,
                'errors' => $validator->errors(),
            ], 422);
        }

        $otp = Otp::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (! $otp) {
            return response()->json(['message' => 'کد تأیید نامعتبر است یا منقضی شده است.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->markEmailAsVerified();
        $user->save();

        $expiresAt = now()->addDays(30);
        $token = $user->createToken('auth_token', [], $expiresAt)->plainTextToken;

        $otp->delete();

        return response()->json([
            'message' => 'احراز هویت با موفقیت انجام شد.',
            'user' => $user,
            'token' => $token,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/login",
     *     summary="ورود با رمز عبور",
     *     description="ورود کاربر با استفاده از ایمیل و رمز عبور",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="ایمیل کاربر"),
     *             @OA\Property(property="password", type="string", format="password", example="123456", description="رمز عبور کاربر")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="ورود موفق",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="ورود کاربر با موفقیت انجام شد."),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="user@example.com")
     *             ),
     *             @OA\Property(property="token", type="string", example="1|randomtokenstring...")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="اطلاعات ورود نادرست یا کاربر احراز هویت نشده",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="پسورد وارد شده اشتباه است.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="مشکل در ورود کاربر."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $messages = [
            'email.required' => 'لطفاً ایمیل را وارد کنید.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
            'password.required' => 'لطفاً رمز عبور را وارد کنید.',
        ];

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ], $messages);

        if ($validator->fails()) {

            $firstError = $validator->errors()->first();

            return response()->json([
                'message' => $firstError,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => 'کاربری با این ایمیل یافت نشد.'], 401);
        }

        if (! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'رمز عبور وارد شده صحیح نیست.'], 401);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'ایمیل شما هنوز تأیید نشده است.'], 401);
        }

        $expiresAt = now()->addDays(30);
        $token = $user->createToken('auth_token', [], $expiresAt)->plainTextToken;

        return response()->json([
            'message' => 'ورود با موفقیت انجام شد.',
            'user' => $user,
            'token' => $token,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/request-otp-login",
     *     summary="درخواست کد برای ورود",
     *     description="ارسال کد به ایمیل کاربر برای ورود بدون رمز عبور",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="ایمیل کاربر")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="کد ارسال شد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="کد ورود برای شما ارسال شد.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="کاربر یافت نشد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="کاربر مورد نظر یافت نشد.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="مشکل در ورود کاربر."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function requestOtpLogin(Request $request): JsonResponse
    {
        $messages = [
            'email.required' => 'لطفاً ایمیل را وارد کنید.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
        ];

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], $messages);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            return response()->json([
                'message' => $firstError,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'message' => 'کاربری با این ایمیل یافت نشد.',
            ], 400);
        }

        $result = $this->sendOtp($user->email, 'ورود');

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            return $result;
        }

        return response()->json([
            'message' => 'کد ورود برای شما ارسال شد.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/login-with-otp",
     *     summary="ورود با کد",
     *     description="ورود کاربر با استفاده از کد",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","code"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="ایمیل کاربر"),
     *             @OA\Property(property="code", type="string", example="123456", description="کد 6 رقمی OTP")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="ورود موفق",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="ورود کاربر با موفقیت انجام شد."),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="user@example.com")
     *             ),
     *             @OA\Property(property="token", type="string", example="1|randomtokenstring...")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="کد نامعتبر یا منقضی شده",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="کد تأیید نامعتبر است.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="مشکل در ورود کاربر."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function loginWithOtp(Request $request): JsonResponse
    {
        $messages = [
            'email.required' => 'لطفاً ایمیل را وارد کنید.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
            'code.required' => 'لطفاً کد تأیید را وارد کنید.',
            'code.string' => 'کد تأیید باید به صورت متن باشد.',
        ];

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string',
        ], $messages);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            return response()->json([
                'message' => $firstError,
                'errors' => $validator->errors(),
            ], 422);
        }

        $otp = Otp::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (! $otp) {
            return response()->json([
                'message' => 'کد تأیید نامعتبر است یا منقضی شده است.',
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'message' => 'کاربری با این ایمیل یافت نشد.',
            ], 400);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $user->save();
        }

        $expiresAt = now()->addDays(30);
        $token = $user->createToken('auth_token', [], $expiresAt)->plainTextToken;

        $otp->delete();

        return response()->json([
            'message' => 'ورود کاربر با موفقیت انجام شد.',
            'user' => $user,
            'token' => $token,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="خروج از حساب کاربری",
     *     description="خروج کاربر و حذف توکن فعلی",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="خروج موفق",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="خروج از حساب با موفقیت انجام شد.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="احراز هویت نشده",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="خطای سرور",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="خطایی در خروج رخ داد.")
     *         )
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if (! $token) {
            return response()->json([
                'message' => 'توکن معتبر یافت نشد.',
            ], 401);
        }

        if ($token->delete()) {
            return response()->json([
                'message' => 'خروج از حساب با موفقیت انجام شد.',
            ]);
        }

        return response()->json([
            'message' => 'خطایی در خروج از حساب رخ داد.',
        ], 500);
    }

    /**
     * @OA\Post(
     *     path="/forgot-password",
     *     summary="درخواست کد برای فراموشی رمز عبور",
     *     description="ارسال کد تأیید به ایمیل کاربر برای بازیابی رمز عبور",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="ایمیل کاربر")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="کد ارسال شد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="کد بازیابی رمز عبور برای شما ارسال شد.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="کاربر یافت نشد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="کاربری با این ایمیل یافت نشد.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="مشکل در درخواست بازیابی رمز عبور."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=429,
     *         description="محدودیت تعداد درخواست",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="شما به محدودیت ارسال کد رسیده‌اید.")
     *         )
     *     )
     * )
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $messages = [
            'email.required' => 'لطفاً ایمیل را وارد کنید.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
        ];

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], $messages);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            return response()->json([
                'message' => $firstError,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'message' => 'کاربری با این ایمیل یافت نشد.',
            ], 400);
        }

        $result = $this->sendOtp($user->email, 'فراموشی رمز عبور');

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            return $result;
        }

        return response()->json([
            'message' => 'کد بازیابی رمز عبور برای شما ارسال شد.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/verify-forgot-password-otp",
     *     summary="تأیید کد فراموشی رمز عبور",
     *     description="بررسی اعتبار کد ارسال شده برای فراموشی رمز عبور",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","code"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="ایمیل کاربر"),
     *             @OA\Property(property="code", type="string", example="1234", description="کد 4 رقمی OTP")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="تأیید موفق",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="کد تأیید شد. می‌توانید رمز عبور جدید را وارد کنید."),
     *             @OA\Property(property="reset_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc...", description="توکن موقت برای تغییر رمز عبور")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="کد نامعتبر یا منقضی شده",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="کد تأیید نامعتبر است یا منقضی شده است.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="مشکل در تأیید کد."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function verifyForgotPasswordOtp(Request $request): JsonResponse
    {
        $messages = [
            'email.required' => 'لطفاً ایمیل را وارد کنید.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
            'code.required' => 'لطفاً کد تأیید را وارد کنید.',
            'code.string' => 'کد تأیید باید به صورت متن باشد.',
        ];

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string',
        ], $messages);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            return response()->json([
                'message' => $firstError,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'message' => 'کاربری با این ایمیل یافت نشد.',
            ], 400);
        }

        $otp = Otp::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (! $otp) {
            return response()->json([
                'message' => 'کد تأیید نامعتبر است یا منقضی شده است.',
            ], 400);
        }

        $resetToken = $user->createToken('password_reset', ['reset-password'], now()->addMinutes(10))->plainTextToken;

        $otp->delete();

        return response()->json([
            'message' => 'کد تأیید شد. می‌توانید رمز عبور جدید را وارد کنید.',
            'reset_token' => $resetToken,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/reset-password",
     *     summary="تنظیم رمز عبور جدید",
     *     description="تغییر رمز عبور پس از تأیید کد OTP",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"new_password","new_password_confirmation"},
     *
     *             @OA\Property(
     *                 property="new_password",
     *                 type="string",
     *                 format="password",
     *                 minLength=6,
     *                 example="newpassword123",
     *                 description="رمز عبور جدید (حداقل 6 کاراکتر)"
     *             ),
     *             @OA\Property(
     *                 property="new_password_confirmation",
     *                 type="string",
     *                 format="password",
     *                 example="newpassword123",
     *                 description="تأیید رمز عبور جدید"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="تغییر رمز عبور موفق",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="رمز عبور با موفقیت تغییر کرد.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="رمز عبور و تأیید آن مطابقت ندارند",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="رمز عبور و تأیید رمز عبور مطابقت ندارند.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="احراز هویت نشده یا توکن نامعتبر",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="توکن فاقد دسترسی لازم",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="این توکن مجاز به تغییر رمز عبور نیست.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="مشکل در تغییر رمز عبور."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="new_password",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The new password must be at least 6 characters.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $messages = [
            'new_password.required' => 'لطفاً رمز عبور جدید را وارد کنید.',
            'new_password.min' => 'رمز عبور جدید باید حداقل ۶ کاراکتر باشد.',
            'new_password_confirmation.required' => 'لطفاً تأیید رمز عبور جدید را وارد کنید.',
        ];

        $validator = Validator::make($request->all(), [
            'new_password' => 'required|min:6',
            'new_password_confirmation' => 'required',
        ], $messages);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            return response()->json([
                'message' => $firstError,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->new_password !== $request->new_password_confirmation) {
            return response()->json([
                'message' => 'رمز عبور و تأیید رمز عبور مطابقت ندارند.',
            ], 400);
        }

        $token = $request->user()->currentAccessToken();

        if (! $request->user()->tokenCan('reset-password')) {
            return response()->json([
                'message' => 'این توکن مجاز به تغییر رمز عبور نیست.',
            ], 403);
        }

        $user = $request->user();
        $user->password = Hash::make($request->new_password);
        $user->save();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'رمز عبور با موفقیت تغییر کرد. لطفاً دوباره وارد شوید.',
        ]);
    }

    // sendOtp
    private function sendOtp($email, $subject)
    {
        $maxRequests = 3;
        $timeLimitHours = 3;

        $windowStart = Carbon::now()->subHours($timeLimitHours);

        $recentOtps = Otp::where('email', $email)
            ->where('created_at', '>=', $windowStart)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($recentOtps->count() >= $maxRequests) {
            $oldestOtp = $recentOtps->first();
            $nextAllowedTime = Carbon::parse($oldestOtp->created_at)->addHours($timeLimitHours);

            $remainingSeconds = now()->diffInSeconds($nextAllowedTime);

            $hours = floor($remainingSeconds / 3600);
            $minutes = ceil(($remainingSeconds % 3600) / 60);

            return response()->json([
                'message' => "شما به محدودیت ارسال کد رسیده‌اید. لطفاً {$hours} ساعت و {$minutes} دقیقه دیگر دوباره تلاش کنید.",
            ], 429);
        }

        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $expiresAt = Carbon::now()->addMinutes(2);

        Otp::create([
            'email' => $email,
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);

        Mail::raw("Code OTP: $code", function ($message) use ($email, $subject) {
            $message->to($email)->subject($subject);
        });
    }
}
