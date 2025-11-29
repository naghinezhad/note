<?php

namespace App\Http\Controllers;

use App\Models\PrivacyPolicy;
use Illuminate\Http\JsonResponse;

class PrivacyPolicyController extends Controller
{
    /**
     * @OA\Get(
     *     path="/privacy-policies",
     *     summary="دریافت لیست سیاست‌های حریم خصوصی",
     *     description="دریافت لیست تمام سیاست‌های حریم خصوصی به ترتیب مرتب‌سازی شده",
     *     tags={"Privacy Policy"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="دریافت موفق لیست سیاست‌های حریم خصوصی",
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
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="جمع‌آوری اطلاعات"),
     *                     @OA\Property(property="content", type="string", example="ما اطلاعات شخصی شما را با رعایت کامل حریم خصوصی جمع‌آوری می‌کنیم"),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z")
     *                 )
     *             )
     *         )
     *     ),
     * )
     */
    public function index(): JsonResponse
    {
        $policies = PrivacyPolicy::orderBy('order')->get();

        return response()->json([
            'data' => $policies,
        ]);
    }
}
