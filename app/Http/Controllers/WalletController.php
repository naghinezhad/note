<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    /**
     * @OA\Get(
     *     path="/wallet",
     *     summary="دریافت اطلاعات کیف پول",
     *     description="دریافت موجودی و اطلاعات کیف پول کاربر",
     *     tags={"Wallet"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="دریافت موفق اطلاعات کیف پول",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="balance", type="number", format="float", example=250000),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
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
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="کیف پول یافت نشد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="کیف پول یافت نشد")
     *         )
     *     )
     * )
     */
    public function show(Request $request): JsonResponse
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
     *     summary="دریافت تراکنش‌های کیف پول",
     *     description="دریافت لیست تراکنش‌های کیف پول با قابلیت فیلتر و مرتب‌سازی",
     *     tags={"Wallet"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="فیلتر بر اساس نوع تراکنش (deposit, withdraw, purchase, refund)",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"deposit", "withdraw", "purchase", "refund"}, example="purchase")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="تعداد تراکنش در هر صفحه",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="شماره صفحه",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="دریافت موفق لیست تراکنش‌ها",
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
     *                     @OA\Property(property="type", type="string", example="purchase", enum={"deposit", "withdraw", "purchase", "refund"}),
     *                     @OA\Property(property="amount", type="number", format="float", example=15000),
     *                     @OA\Property(property="balance_before", type="number", format="float", example=250000),
     *                     @OA\Property(property="balance_after", type="number", format="float", example=235000),
     *                     @OA\Property(property="description", type="string", example="خرید محصول: لپ تاپ ایسوس"),
     *                     @OA\Property(property="product_id", type="integer", nullable=true, example=1),
     *                     @OA\Property(property="reference_code", type="string", nullable=true, example="TR-20241130-A1B2C3"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
     *                     @OA\Property(
     *                         property="product",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="لپ تاپ ایسوس")
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
     *         description="احراز هویت نشده",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="کیف پول یافت نشد",
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

        $query = $wallet->transactions()->with('product:id,name');

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

    /**
     * @OA\Post(
     *     path="/wallet/deposit",
     *     summary="شارژ کیف پول",
     *     description="افزایش موجودی کیف پول",
     *     tags={"Wallet"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"amount"},
     *
     *             @OA\Property(property="amount", type="number", format="float", example=100000, description="مبلغ شارژ (حداقل ۱۰۰۰ تومان)"),
     *             @OA\Property(property="reference_code", type="string", example="PAY-123456789", description="کد پیگیری پرداخت (اختیاری - حداکثر ۵۰ کاراکتر)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="شارژ موفق",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="کیف پول با موفقیت شارژ شد"),
     *             @OA\Property(
     *                 property="transaction",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(property="wallet_id", type="integer", example=1),
     *                 @OA\Property(property="type", type="string", example="deposit"),
     *                 @OA\Property(property="amount", type="number", format="float", example=100000),
     *                 @OA\Property(property="balance_before", type="number", format="float", example=250000),
     *                 @OA\Property(property="balance_after", type="number", format="float", example=350000),
     *                 @OA\Property(property="description", type="string", example="شارژ کیف پول"),
     *                 @OA\Property(property="product_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="reference_code", type="string", nullable=true, example="PAY-123456789"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
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
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="کیف پول یافت نشد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="کیف پول یافت نشد")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="لطفاً مبلغ را وارد کنید."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="amount",
     *                     type="array",
     *                     @OA\Items(type="string", example="مبلغ باید حداقل ۱۰۰۰ تومان باشد.")
     *                 ),
     *                 @OA\Property(
     *                     property="reference_code",
     *                     type="array",
     *                     @OA\Items(type="string", example="کد پیگیری نباید بیشتر از ۵۰ کاراکتر باشد.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="خطای سرور",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="خطا در شارژ کیف پول")
     *         )
     *     )
     * )
     */
    public function deposit(Request $request): JsonResponse
    {
        $messages = [
            'amount.required' => 'لطفاً مبلغ را وارد کنید.',
            'amount.numeric' => 'مبلغ باید عدد باشد.',
            'amount.min' => 'مبلغ باید حداقل ۱۰۰۰ تومان باشد.',
            'reference_code.string' => 'کد پیگیری باید متن باشد.',
            'reference_code.max' => 'کد پیگیری نباید بیشتر از ۵۰ کاراکتر باشد.',
        ];

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1000',
            'reference_code' => 'nullable|string|max:50',
        ], $messages);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            return response()->json([
                'message' => $firstError,
                'errors' => $validator->errors(),
            ], 422);
        }

        $wallet = $request->user()->wallet;

        if (! $wallet) {
            return response()->json([
                'message' => 'کیف پول یافت نشد',
            ], 404);
        }

        $transaction = $wallet->deposit(
            $request->amount,
            'شارژ کیف پول',
            $request->reference_code
        );

        return response()->json([
            'message' => 'کیف پول با موفقیت شارژ شد',
            'transaction' => $transaction,
        ]);
    }
}
