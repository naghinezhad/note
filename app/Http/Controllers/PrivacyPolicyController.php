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
     *     summary="",
     *     description="",
     *     tags={"Privacy Policy"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="",
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
     *                     @OA\Property(property="title", type="string", example=""),
     *                     @OA\Property(property="content", type="string", example=""),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example=""),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="")
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
