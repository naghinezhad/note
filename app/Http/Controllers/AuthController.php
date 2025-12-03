<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfileResource;
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
     *     summary="",
     *     description="",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description=""),
     *             @OA\Property(property="password", type="string", format="password", minLength=6, example="123456", description="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="password",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function register(Request $request): JsonResponse
    {
        // Start Validator
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
        // End Validator

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
     *     summary="",
     *     description="",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","code"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description=""),
     *             @OA\Property(property="code", type="string", example="123456", description="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="token", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        // Start Validator
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
        // End Validator

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
            'user' => new ProfileResource($user),
            'token' => $token,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/login",
     *     summary="",
     *     description="",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description=""),
     *             @OA\Property(property="password", type="string", format="password", example="123456", description="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="user@example.com")
     *             ),
     *             @OA\Property(property="token", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        // Start Validator
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
        // End Validator

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
            'user' => new ProfileResource($user),
            'token' => $token,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/request-otp-login",
     *     summary="",
     *     description="",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function requestOtpLogin(Request $request): JsonResponse
    {
        // Start Validator
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
        // End Validator

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
     *     summary="",
     *     description="",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","code"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description=""),
     *             @OA\Property(property="code", type="string", example="123456", description="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="user@example.com")
     *             ),
     *             @OA\Property(property="token", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function loginWithOtp(Request $request): JsonResponse
    {
        // Start Validator
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
        // End Validator

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
            'user' => new ProfileResource($user),
            'token' => $token,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="",
     *     description="",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
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
     *     summary="",
     *     description="",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=429,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     )
     * )
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        // Start Validator
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
        // End Validator

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
     *     summary="",
     *     description="",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","code"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description=""),
     *             @OA\Property(property="code", type="string", example="1234", description="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(property="reset_token", type="string", example="", description="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function verifyForgotPasswordOtp(Request $request): JsonResponse
    {
        // Start Validator
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
        // End Validator

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
     *     summary="",
     *     description="",
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
     *                 description=""
     *             ),
     *             @OA\Property(
     *                 property="new_password_confirmation",
     *                 type="string",
     *                 format="password",
     *                 example="newpassword123",
     *                 description=""
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="new_password",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function resetPassword(Request $request): JsonResponse
    {
        // Start Validator
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
        // End Validator

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
