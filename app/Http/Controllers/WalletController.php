<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * @OA\Get(
     *     path="/wallet",
     *     summary="",
     *     description="",
     *     tags={"Wallet"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="coins", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example=""),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="")
     *             )
     *         )
     *     ),
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet;

        if (! $wallet) {
            return response()->json([
                'message' => 'کیف پول یافت نشد',
            ], 404);
        }

        return response()->json([
            'data' => $wallet,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/wallet/transactions",
     *     summary="",
     *     description="",
     *     tags={"Wallet"},
     *     security={{"bearerAuth":{}}},
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
     *                     @OA\Property(property="wallet_id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", example=""),
     *                     @OA\Property(property="coins", type="integer", example=1),
     *                     @OA\Property(property="coins_before", type="integer", example=1),
     *                     @OA\Property(property="coins_after", type="integer", example=1),
     *                     @OA\Property(property="paid_amount", type="integer", example=1),
     *                     @OA\Property(property="description", type="string", example=""),
     *                     @OA\Property(property="product_id", type="integer", example=1),
     *                     @OA\Property(property="coin_package_id", type="integer", example=1),
     *                     @OA\Property(property="reference_code", type="string", example=""),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example=""),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example=""),
     *                     @OA\Property(
     *                         property="product",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example=""),
     *                         @OA\Property(property="high_quality_image", type="string", example=""),
     *                         @OA\Property(property="low_quality_image", type="string", example=""),
     *                         @OA\Property(property="price", type="integer", example=1),
     *                         @OA\Property(property="description", type="string", example=""),
     *                         @OA\Property(property="likes", type="integer", example=1),
     *                         @OA\Property(property="views", type="integer", example=1),
     *                         @OA\Property(property="purchased", type="integer", example=1),
     *                         @OA\Property(property="category_id", type="integer", example=1),
     *                         @OA\Property(property="is_active", type="boolean", example=true),
     *                         @OA\Property(property="is_3d", type="boolean", example=false),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example=""),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="")
     *                     ),
     *                     @OA\Property(
     *                         property="coin_package",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example=""),
     *                         @OA\Property(property="coins", type="integer", example=1),
     *                         @OA\Property(property="price", type="integer", example=1),
     *                         @OA\Property(property="discount_percentage", type="integer", example=1),
     *                         @OA\Property(property="is_active", type="boolean", example=true),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example=""),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example=""),
     *                         @OA\Property(property="final_price", type="integer", example=1),
     *                         @OA\Property(property="discount_amount", type="integer", example=1)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=1),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="to", type="integer", example=1)
     *             )
     *         )
     *     )
     * )
     */
    public function transactions(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet;

        if (! $wallet) {
            return response()->json([
                'message' => 'کیف پول یافت نشد',
            ], 404);
        }

        $query = $wallet->transactions()->with([
            'product',
            'coinPackage',
        ]);

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        $perPage = $request->get('per_page', 10);
        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $transactions->items(),
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
        ]);
    }
}
