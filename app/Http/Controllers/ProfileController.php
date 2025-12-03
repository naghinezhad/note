<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
     *             @OA\Property(property="name", type="string", example=""),
     *             @OA\Property(property="email", type="string", example="")
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
     *                 @OA\Property(property="name", type="string", example=""),
     *                 @OA\Property(property="email", type="string", example="")
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
        $messages = [
            'email.email' => 'لطفاً یک ایمیل معتبر وارد کنید.',
            'email.unique' => 'ایمیل وارد شده قبلاً ثبت شده است.',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,'.$user->id,
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }
        // End Validator

        if ($request->email && $request->email !== $user->email) {
            $user->email_verified_at = null;
            $user->email = $request->email;
        }

        $user->name = $request->name ?? $user->name;

        $user->save();

        return response()->json([
            'message' => 'پروفایل با موفقیت آپدیت شد.',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ],
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
}
