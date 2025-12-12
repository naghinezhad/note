<?php

namespace App\Http\Controllers;

use App\Http\Resources\PrivacyPolicyResource;
use App\Models\PrivacyPolicy;
use Illuminate\Http\JsonResponse;

class PrivacyPolicyController extends Controller
{
    /**
     * @OA\Get(
     *     path="/privacy-policies",
     *     summary="دریافت سیاست حفاظت از حریم خصوصی",
     *     description="بازیابی تمام بندهای سیاست حفاظت از حریم خصوصی به ترتیب نمایش. این اطلاعات برای هر کاربری درخواست‌شده می‌تواند تهیه شود و شامل تمام بند‌های مرتب‌شده‌ای است که توسط مدیر سیستم تعریف شده‌اند.",
     *     tags={"Privacy Policy"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="لیست سیاست حفاظت از حریم خصوصی با موفقیت دریافت شد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *                     type="object",
     *
     *                     @OA\Property(property="id", type="integer", example=1, description="شناسه منحصر بند"),
     *                     @OA\Property(property="title", type="string", example="عنوان سیاست", description="عنوان بند سیاست"),
     *                     @OA\Property(property="content", type="string", example="متن کامل سیاست حفاظت از حریم خصوصی شامل تمام جزئیات مربوط به جمع‌آوری و استفاده از اطلاعات کاربران", description="محتوای کامل بند سیاست"),
     *                     @OA\Property(property="order", type="integer", example=1, description="ترتیب نمایش بند (کوچک‌تر = ابتدا نمایش)"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-15T10:30:00Z", description="تاریخ ایجاد بند"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-06-20T14:45:00Z", description="تاریخ آخرین به‌روزرسانی")
     *                 )
     *             )
     *         )
     *     ),
     * )
     */
    public function index(): JsonResponse
    {
        $privacyPolicy = PrivacyPolicy::orderBy('order')->get();

        return response()->json([
            'data' => PrivacyPolicyResource::collection($privacyPolicy),
        ]);
    }
}
