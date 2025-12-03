<?php

namespace App\Http\Controllers;

use App\Models\CoinPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CoinPackageController extends Controller
{
    /**
     * @OA\Post(
     *     path="/coin-packages/purchase-package",
     *     summary="",
     *     description="",
     *     tags={"Coin Packages"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"coin_package_id", "paid_amount", "reference_code"},
     *
     *             @OA\Property(property="coin_package_id", type="integer", example=1, description=""),
     *             @OA\Property(property="paid_amount", type="integer", example=1, description=""),
     *             @OA\Property(property="reference_code", type="string", example="abc", description="")
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
     *                 property="transaction",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="wallet_id", type="integer", example=1),
     *                 @OA\Property(property="type", type="string", example=""),
     *                 @OA\Property(property="coins", type="integer", example=1),
     *                 @OA\Property(property="coins_before", type="integer", example=1),
     *                 @OA\Property(property="coins_after", type="integer", example=1),
     *                 @OA\Property(property="paid_amount", type="integer", example=1),
     *                 @OA\Property(property="description", type="string", example=""),
     *                 @OA\Property(property="coin_package_id", type="integer", example=1),
     *                 @OA\Property(property="reference_code", type="string", example=""),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example=""),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = CoinPackage::query();

        $query->where('is_active', true);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $packages = $query->orderBy('price', 'asc')->get();

        return response()->json([
            'data' => $packages,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/coin-packages/purchase-package",
     *     summary="Purchase a coin package",
     *     description="Handles the purchase of a selected coin package, validates payment, and updates the user's wallet.",
     *     tags={"Coin Packages"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"coin_package_id", "paid_amount", "reference_code"},
     *
     *             @OA\Property(property="coin_package_id", type="integer", example=1, description="ID of the coin package to purchase"),
     *             @OA\Property(property="paid_amount", type="integer", example=100, description="Amount paid for the package"),
     *             @OA\Property(property="reference_code", type="string", example="abc123", description="Payment reference code")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful purchase",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Package purchased successfully"),
     *             @OA\Property(
     *                 property="transaction",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="wallet_id", type="integer", example=1),
     *                 @OA\Property(property="type", type="string", example="purchase"),
     *                 @OA\Property(property="coins", type="integer", example=100),
     *                 @OA\Property(property="coins_before", type="integer", example=0),
     *                 @OA\Property(property="coins_after", type="integer", example=100),
     *                 @OA\Property(property="paid_amount", type="integer", example=100),
     *                 @OA\Property(property="description", type="string", example="Coin package purchase"),
     *                 @OA\Property(property="coin_package_id", type="integer", example=1),
     *                 @OA\Property(property="reference_code", type="string", example="abc123"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z")
     *             )
     *         )
     *     )
     * )
     */
    public function purchasePackage(Request $request): JsonResponse
    {
        // Start Validator
        $messages = [
            'coin_package_id.required' => 'لطفاً پکیج را انتخاب کنید.',
            'coin_package_id.exists' => 'پکیج انتخابی معتبر نیست.',
            'paid_amount.required' => 'لطفاً مبلغ پرداختی را وارد کنید.',
            'paid_amount.integer' => 'مبلغ پرداختی باید عدد صحیح باشد.',
            'paid_amount.min' => 'مبلغ پرداختی باید بیشتر از صفر باشد.',
            'pay_reference_code.required' => 'لطفا کد پیگیری پرداخت را وارد کنید.',
            'pay_reference_code.string' => 'کد پیگیری پرداخت باید متن باشد.',
        ];

        $validator = Validator::make($request->all(), [
            'coin_package_id' => 'required|exists:coin_packages,id',
            'paid_amount' => 'required|integer|min:1',
            'pay_reference_code' => 'required|string',
        ], $messages);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            return response()->json([
                'message' => $firstError,
                'errors' => $validator->errors(),
            ], 422);
        }
        // End Validator

        $wallet = $request->user()->wallet;

        if (! $wallet) {
            return response()->json([
                'message' => 'کیف پول یافت نشد',
            ], 404);
        }

        $package = CoinPackage::find($request->coin_package_id);

        if (! $package || ! $package->is_active) {
            return response()->json([
                'message' => 'پکیج انتخابی در دسترس نیست',
            ], 404);
        }

        if ($request->paid_amount != $package->final_price) {
            return response()->json([
                'message' => 'مبلغ پرداختی باید دقیقاً برابر با قیمت نهایی پکیج باشد',
                'required_amount' => $package->final_price,
                'paid_amount' => $request->paid_amount,
            ], 422);
        }

        try {
            DB::beginTransaction();

            $referenceCode = 'NOTIN-RC-PACKAGE-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -6));

            $transaction = $wallet->purchasePackage(
                $package,
                $request->paid_amount,
                $request->referenceCode
            );

            DB::commit();

            return response()->json([
                'message' => 'پکیج با موفقیت خریداری شد',
                'transaction' => $transaction,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'خطا در خرید پکیج: '.$e->getMessage(),
            ], 500);
        }
    }
}
