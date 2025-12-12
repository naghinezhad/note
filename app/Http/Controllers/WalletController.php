<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * @OA\Get(
     *     path="/wallet",
     *     summary="دریافت موجودی کیف پول",
     *     description="بازیابی اطلاعات کیف پول و موجودی کوین‌های کاربر فعلی",
     *     tags={"Wallet"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="اطلاعات کیف پول دریافت شد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="coins", type="integer", example=500, description="موجودی کوین در کیف پول"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="عدم احراز هویت",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="کیف پول پیدا نشد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="کیف پول یافت نشد")
     *         )
     *     )
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
     *     summary="دریافت تاریخچه تراکنش‌های کیف پول",
     *     description="بازیابی لیست تمام تراکنش‌های کیف پول کاربر فعلی با اطلاعات جزئی",
     *     tags={"Wallet"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="فیلتر بر اساس نوع تراکنش (purchase=خریداری محصول، buy=خریداری پکیج)",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="purchase")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="تعداد تراکنش‌ها در هر صفحه",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="لیست تراکنش‌ها دریافت شد",
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
     *                     @OA\Property(property="type", type="string", example="purchase", description="نوع تراکنش (purchase یا buy)"),
     *                     @OA\Property(property="coins", type="integer", example=-100, description="تغییر در کوین (منفی برای خریداری، مثبت برای خرید پکیج)"),
     *                     @OA\Property(property="coins_before", type="integer", example=500, description="موجودی قبل از تراکنش"),
     *                     @OA\Property(property="coins_after", type="integer", example=400, description="موجودی بعد از تراکنش"),
     *                     @OA\Property(property="paid_amount", type="integer", example=100),
     *                     @OA\Property(property="description", type="string", example="خریداری محصول"),
     *                     @OA\Property(property="product_id", type="integer", example=1, description="شناسه محصول (null برای خریداری پکیج)"),
     *                     @OA\Property(property="coin_package_id", type="integer", example=null, description="شناسه پکیج (null برای خریداری محصول)"),
     *                     @OA\Property(property="reference_code", type="string", example="NOTIN-RC-BOOK-20230101-ABCDEF"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
     *                     @OA\Property(
     *                         property="product",
     *                         type="object",
     *                         nullable=true,
     *                         description="اطلاعات محصول (فقط اگر product_id موجود باشد)",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="تصویر سه بعدی"),
     *                         @OA\Property(property="high_quality_image", type="string", example="https://example.com/storage/images/product-1-hq.jpg"),
     *                         @OA\Property(property="low_quality_image", type="string", example="https://example.com/storage/images/product-1-lq.jpg"),
     *                         @OA\Property(property="price", type="integer", example=100),
     *                         @OA\Property(property="description", type="string", example="توضیح محصول"),
     *                         @OA\Property(property="likes", type="integer", example=25),
     *                         @OA\Property(property="views", type="integer", example=150),
     *                         @OA\Property(property="purchased", type="integer", example=10),
     *                         @OA\Property(property="category_id", type="integer", example=1),
     *                         @OA\Property(property="is_active", type="boolean", example=true),
     *                         @OA\Property(property="is_3d", type="boolean", example=true),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z")
     *                     ),
     *                     @OA\Property(
     *                         property="coin_package",
     *                         type="object",
     *                         nullable=true,
     *                         description="اطلاعات پکیج (فقط اگر coin_package_id موجود باشد)",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="پکیج 100 کوین"),
     *                         @OA\Property(property="coins", type="integer", example=100),
     *                         @OA\Property(property="price", type="integer", example=100000, description="قیمت اصلی"),
     *                         @OA\Property(property="discount_percentage", type="integer", example=10),
     *                         @OA\Property(property="is_active", type="boolean", example=true),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
     *                         @OA\Property(property="final_price", type="integer", example=90000, description="قیمت بعد از تخفیف"),
     *                         @OA\Property(property="discount_amount", type="integer", example=10000, description="مبلغ تخفیف")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="to", type="integer", example=10)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="عدم احراز هویت",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="کیف پول پیدا نشد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="کیف پول یافت نشد")
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
