<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
}
