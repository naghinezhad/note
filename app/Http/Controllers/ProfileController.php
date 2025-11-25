<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/profile",
     *     summary="دریافت اطلاعات پروفایل",
     *     description="دریافت اطلاعات کاربر لاگین شده",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="دریافت موفق اطلاعات",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="اطلاعات کاربر با موفقیت دریافت شد."),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T08:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z")
     *             )
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
     *     )
     * )
     */
    public function profile(Request $request)
    {
        return response()->json([
            'message' => 'اطلاعات کاربر با موفقیت دریافت شد.',
            'user' => $request->user(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/change-password",
     *     summary="تغییر رمز عبور",
     *     description="تغییر رمز عبور کاربر با استفاده از رمز عبور فعلی و رمز عبور جدید",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"current_password","new_password"},
     *
     *             @OA\Property(
     *                 property="current_password",
     *                 type="string",
     *                 format="password",
     *                 example="old123456",
     *                 description="رمز عبور فعلی کاربر"
     *             ),
     *             @OA\Property(
     *                 property="new_password",
     *                 type="string",
     *                 format="password",
     *                 minLength=6,
     *                 example="new123456",
     *                 description="رمز عبور جدید (حداقل 6 کاراکتر)"
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
     *             @OA\Property(property="message", type="string", example="رمز عبور کاربر با موفقیت تغییر کرد.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="رمز عبور فعلی اشتباه است",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="رمز عبور فعلی کاربر اشتباه است.")
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
     *         response=422,
     *         description="خطای اعتبارسنجی",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="مشکل در احراز هویت کاربر."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="current_password",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The current password field is required.")
     *                 ),
     *
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
    public function changePassword(Request $request)
    {
        $messages = [
            'current_password.required' => 'لطفاً رمز عبور فعلی را وارد کنید.',
            'new_password.required' => 'لطفاً رمز عبور جدید را وارد کنید.',
            'new_password.min' => 'رمز عبور جدید باید حداقل ۶ کاراکتر باشد.',
        ];

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:6',
        ], $messages);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            return response()->json([
                'message' => $firstError,
                'errors' => $validator->errors(),
            ], 422);
        }

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
}
