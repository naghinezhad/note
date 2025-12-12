<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfileResource;
use App\Models\Otp;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/profile",
     *     summary="",
     *     description="",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example=""),
     *                 @OA\Property(property="email", type="string", example=""),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time", example=""),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example=""),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new ProfileResource($request->user()),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/change-password",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"current_password","new_password","new_password_confirmation"},
     *
     *             @OA\Property(property="current_password", type="string", example=""),
     *             @OA\Property(property="new_password", type="string", example=""),
     *             @OA\Property(property="new_password_confirmation", type="string", example="")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function changePassword(Request $request): JsonResponse
    {
        // Start Validator
        $messages = [
            'current_password.required' => 'لطفاً رمز عبور فعلی را وارد کنید.',
            'new_password.required' => 'لطفاً رمز عبور جدید را وارد کنید.',
            'new_password.min' => 'رمز عبور جدید باید حداقل ۶ کاراکتر باشد.',
            'new_password.confirmed' => 'تایید رمز عبور جدید با آن مطابقت ندارد.',
        ];

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ], $messages);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            return response()->json([
                'message' => $firstError,
                'errors' => $validator->errors(),
            ], 422);
        }
        // End Validator

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'رمز عبور فعلی کاربر اشتباه است.',
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'رمز عبور کاربر با موفقیت تغییر کرد.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/edit-profile",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=false,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string", example="")
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
     *                 @OA\Property(property="name", type="string", example="")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
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
     *     )
     * )
     */
    public function editProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        // Start Validator
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }
        // End Validator

        $user->name = $request->name ?? $user->name;
        $user->save();

        return response()->json([
            'message' => 'کاربر با موفقیت آپدیت شد.',
            'user' => new ProfileResource($user),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/change-profile-image",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"image_profile"},
     *
     *                 @OA\Property(
     *                     property="image_profile",
     *                     type="string",
     *                     format="binary",
     *                     description=""
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Profile image updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(property="image_profile", type="string", example=""),
     *             @OA\Property(property="image_profile_signed", type="string", example=""),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
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
     *     )
     * )
     */
    public function changeProfileImage(Request $request): JsonResponse
    {
        // Start Validator
        $messages = [
            'image_profile.required' => 'لطفاً یک عکس انتخاب کنید.',
            'image_profile.image' => 'فایل انتخاب شده باید یک تصویر باشد.',
            'image_profile.max' => 'اندازه تصویر نباید بیشتر از 2MB باشد.',
        ];

        $validator = Validator::make($request->all(), [
            'image_profile' => 'required|image|max:2048',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }
        // End Validator

        $user = $request->user();

        if ($user->image_profile && Storage::disk('private')->exists($user->image_profile)) {
            Storage::disk('private')->delete($user->image_profile);
        }

        $file = $request->file('image_profile');
        $path = $file->store('', 'private');

        $user->image_profile = $path;
        $user->save();

        return response()->json([
            'message' => 'عکس پروفایل با موفقیت به‌روزرسانی شد.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/request-change-email",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"new_email"},
     *
     *             @OA\Property(property="new_email", type="string", format="email", example="")
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
    public function requestChangeEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        // Start Validator
        $messages = [
            'new_email.required' => 'لطفاً ایمیل جدید را وارد کنید.',
            'new_email.email' => 'فرمت ایمیل صحیح نیست.',
            'new_email.unique' => 'این ایمیل قبلاً ثبت شده است.',
        ];

        $validator = Validator::make($request->all(), [
            'new_email' => 'required|email|max:255|unique:users,email',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }
        // End Validator

        if ($request->new_email === $user->email) {
            return response()->json([
                'message' => 'ایمیل جدید نمی‌تواند با ایمیل فعلی یکسان باشد.',
            ], 422);
        }

        $result = $this->sendOtp($request->new_email, 'تغییر ایمیل');

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            return $result;
        }

        return response()->json([
            'message' => 'کد تأیید به ایمیل جدید ارسال شد.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/change-email",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"old_email","new_email","code"},
     *
     *             @OA\Property(property="old_email", type="string", format="email", example=""),
     *             @OA\Property(property="new_email", type="string", format="email", example=""),
     *             @OA\Property(property="code", type="string", example="")
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
     *                 @OA\Property(property="email", type="string", example=""),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time", example="")
     *             )
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
     *     )
     * )
     */
    public function changeEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        // Start Validator
        $messages = [
            'old_email.required' => 'لطفاً ایمیل فعلی را وارد کنید.',
            'old_email.email' => 'فرمت ایمیل فعلی صحیح نیست.',
            'new_email.required' => 'لطفاً ایمیل جدید را وارد کنید.',
            'new_email.email' => 'فرمت ایمیل جدید صحیح نیست.',
            'new_email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'code.required' => 'لطفاً کد تأیید را وارد کنید.',
            'code.string' => 'کد تأیید باید به صورت متن باشد.',
        ];

        $validator = Validator::make($request->all(), [
            'old_email' => 'required|email',
            'new_email' => 'required|email|max:255|unique:users,email',
            'code' => 'required|string',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }
        // End Validator

        if ($request->old_email !== $user->email) {
            return response()->json([
                'message' => 'ایمیل فعلی با ایمیل حساب کاربری شما مطابقت ندارد.',
            ], 400);
        }

        if ($request->new_email === $user->email) {
            return response()->json([
                'message' => 'ایمیل جدید نمی‌تواند با ایمیل فعلی یکسان باشد.',
            ], 400);
        }

        $otp = Otp::where('email', $request->new_email)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (! $otp) {
            return response()->json([
                'message' => 'کد تأیید نامعتبر است یا منقضی شده است.',
            ], 400);
        }

        $user->email = $request->new_email;
        $user->email_verified_at = Carbon::now();
        $user->save();

        $otp->delete();

        return response()->json([
            'message' => 'ایمیل با موفقیت تغییر کرد.',
            'user' => new ProfileResource($user),
        ]);
    }

    // sendOtp
    private function sendOtp($email, $subject, $useLimit = false)
    {
        $maxRequests = 3;
        $timeLimitHours = 3;

        if ($useLimit) {
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

        return true;
    }
}
