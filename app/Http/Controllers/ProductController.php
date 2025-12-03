<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/products",
     *     summary="",
     *     description="",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="")
     *     ),
     *
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="(newest, oldest, most_liked, most_purchased, most_viewed, price_high, price_low)",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="string",
     *             enum={"newest", "oldest", "most_liked", "most_purchased", "most_viewed", "price_high", "price_low"},
     *             example=""
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
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
     *                     @OA\Property(property="name", type="string", example=""),
     *                     @OA\Property(property="high_quality_image", type="string", example=""),
     *                     @OA\Property(property="low_quality_image", type="string", example=""),
     *                     @OA\Property(property="price", type="integer", example=1),
     *                     @OA\Property(property="description", type="string", example=""),
     *                     @OA\Property(property="likes", type="integer", example=1),
     *                     @OA\Property(property="views", type="integer", example=1),
     *                     @OA\Property(property="purchased", type="integer", example=1),
     *                     @OA\Property(property="category_id", type="integer", example=1),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="is_3d", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="string", example=""),
     *                     @OA\Property(property="updated_at", type="string", example=""),
     *                     @OA\Property(property="is_free", type="boolean", example=false),
     *                     @OA\Property(property="is_purchased", type="boolean", example=false),
     *                     @OA\Property(property="is_liked", type="boolean", example=false),
     *                     @OA\Property(
     *                         property="category",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example=""),
     *                         @OA\Property(property="color", type="string", example=""),
     *                         @OA\Property(property="description", type="string", example=""),
     *                         @OA\Property(property="order", type="integer", example=1),
     *                         @OA\Property(property="created_at", type="string", example=""),
     *                         @OA\Property(property="updated_at", type="string", example="")
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
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->where('is_active', true);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $sortBy = $request->get('sort_by', 'newest');

        switch ($sortBy) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;

            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;

            case 'most_liked':
                $query->orderBy('likes', 'desc');
                break;

            case 'most_purchased':
                $query->orderBy('purchased', 'desc');
                break;

            case 'most_viewed':
                $query->orderBy('views', 'desc');
                break;

            case 'price_high':
                $query->orderBy('price', 'desc');
                break;

            case 'price_low':
                $query->orderBy('price', 'asc');
                break;

            default:
                $query->orderBy('created_at', 'desc');
        }

        $perPage = $request->get('per_page', 10);
        $products = $query->with('category')->paginate($perPage);

        $user = $request->user();

        $productsData = $products->map(function ($product) use ($user) {
            $productArray = $product->toArray();
            $productArray['is_free'] = $product->price == 0;
            $productArray['is_purchased'] = $user ? $product->purchasedUsers()->where('user_id', $user->id)->exists() : false;
            $productArray['is_liked'] = $user ? $product->likedUsers()->where('user_id', $user->id)->exists() : false;

            $productArray['high_quality_image'] = URL::temporarySignedRoute(
                'signed.file',
                now()->addMinute(),
                ['path' => $product->high_quality_image]
            );

            $productArray['low_quality_image'] = URL::temporarySignedRoute(
                'signed.file',
                now()->addMinute(),
                ['path' => $product->low_quality_image]
            );

            return $productArray;
        });

        return response()->json([
            'data' => $productsData,
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/products/my-purchases",
     *     summary="",
     *     description="",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="")
     *     ),
     *
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="(newest, oldest, price_high, price_low)",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="string",
     *             enum={"newest", "oldest", "price_high", "price_low"},
     *             example=""
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
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
     *                     @OA\Property(property="name", type="string", example=""),
     *                     @OA\Property(property="high_quality_image", type="string", example=""),
     *                     @OA\Property(property="low_quality_image", type="string", example=""),
     *                     @OA\Property(property="price", type="integer", example=1),
     *                     @OA\Property(property="description", type="string", example=""),
     *                     @OA\Property(property="likes", type="integer", example=1),
     *                     @OA\Property(property="views", type="integer", example=1),
     *                     @OA\Property(property="purchased", type="integer", example=1),
     *                     @OA\Property(property="category_id", type="integer", example=1),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="is_3d", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="string", example=""),
     *                     @OA\Property(property="updated_at", type="string", example=""),
     *                     @OA\Property(property="is_free", type="boolean", example=false),
     *                     @OA\Property(property="is_purchased", type="boolean", example=true),
     *                     @OA\Property(property="is_liked", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="category",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example=""),
     *                         @OA\Property(property="color", type="string", example=""),
     *                         @OA\Property(property="description", type="string", example=""),
     *                         @OA\Property(property="order", type="integer", example=1),
     *                         @OA\Property(property="created_at", type="string", example=""),
     *                         @OA\Property(property="updated_at", type="string", example="")
     *                     ),
     *                     @OA\Property(
     *                         property="pivot",
     *                         type="object",
     *                         @OA\Property(property="user_id", type="integer", example=1),
     *                         @OA\Property(property="product_id", type="integer", example=1),
     *                         @OA\Property(property="created_at", type="string", example=""),
     *                         @OA\Property(property="updated_at", type="string", example="")
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
    public function myPurchases(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->purchasedProducts()->where('is_active', true);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $sortBy = $request->get('sort_by', 'newest');

        switch ($sortBy) {
            case 'newest':
                $query->orderBy('product_user_purchased.created_at', 'desc');
                break;

            case 'oldest':
                $query->orderBy('product_user_purchased.created_at', 'asc');
                break;

            case 'price_high':
                $query->orderBy('price', 'desc');
                break;

            case 'price_low':
                $query->orderBy('price', 'asc');
                break;

            default:
                $query->orderBy('product_user_purchased.created_at', 'desc');
        }

        $perPage = $request->get('per_page', 10);
        $products = $query->with('category')
            ->paginate($perPage);

        $productsData = $products->map(function ($product) use ($user) {
            $productArray = $product->toArray();
            $productArray['is_free'] = $product->price == 0;
            $productArray['is_purchased'] = $user ? $product->purchasedUsers()->where('user_id', $user->id)->exists() : false;
            $productArray['is_liked'] = $user ? $product->likedUsers()->where('user_id', $user->id)->exists() : false;

            $productArray['high_quality_image'] = URL::temporarySignedRoute(
                'signed.file',
                now()->addMinute(),
                ['path' => $product->high_quality_image]
            );

            $productArray['low_quality_image'] = URL::temporarySignedRoute(
                'signed.file',
                now()->addMinute(),
                ['path' => $product->low_quality_image]
            );

            return $productArray;
        });

        return response()->json([
            'data' => $productsData,
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/products/{id}",
     *     summary="",
     *     description="",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=2)
     *     ),
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
     *                 @OA\Property(property="name", type="string", example=""),
     *                 @OA\Property(property="description", type="string", example=""),
     *                 @OA\Property(property="price", type="number", format="float", example=1),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="likes", type="integer", example=1),
     *                 @OA\Property(property="purchased", type="integer", example=1),
     *                 @OA\Property(property="views", type="integer", example=1),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="is_3d", type="boolean", example=true),
     *                 @OA\Property(property="is_free", type="boolean", example=true),
     *                 @OA\Property(property="is_purchased", type="boolean", example=true),
     *                 @OA\Property(property="is_liked", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="category",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example=""),
     *                     @OA\Property(property="color", type="string", example="")
     *                 )
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
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     )
     * )
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $product = Product::with('category')->find($id);

        if (! $product) {
            return response()->json([
                'message' => 'محصول یافت نشد',
            ], 404);
        }

        $user = $request->user();

        if ($user && ! $product->viewedUsers()->where('user_id', $user->id)->exists()) {
            $product->viewedUsers()->attach($user->id);
            $product->increment('views');
        }

        $productData = $product->toArray();
        $productData['is_free'] = $product->price == 0;
        $productData['is_purchased'] = $user ? $product->purchasedUsers()->where('user_id', $user->id)->exists() : false;
        $productData['is_liked'] = $user ? $product->likedUsers()->where('user_id', $user->id)->exists() : false;

        $productData['high_quality_image'] = URL::temporarySignedRoute(
            'signed.file',
            now()->addMinute(),
            ['path' => $product->high_quality_image]
        );

        $productData['low_quality_image'] = URL::temporarySignedRoute(
            'signed.file',
            now()->addMinute(),
            ['path' => $product->low_quality_image]
        );

        return response()->json([
            'data' => $productData,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/products/like/{id}",
     *     summary="",
     *     description="       ",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="",
     *
     *         @OA\JsonContent(
     *             oneOf={
     *
     *                 @OA\Schema(
     *
     *                     @OA\Property(property="message", type="string", example=""),
     *                 ),
     *
     *                 @OA\Schema(
     *
     *                     @OA\Property(property="message", type="string", example=""),
     *                 )
     *             }
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
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="")
     *         )
     *     )
     * )
     */
    public function like(int $id, Request $request): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'message' => 'محصول یافت نشد',
            ], 404);
        }

        $user = $request->user();
        $liked = $product->likedUsers()->where('user_id', $user->id)->exists();

        if ($liked) {
            $product->likedUsers()->detach($user->id);
            $product->decrement('likes');

            return response()->json([
                'message' => 'لایک برداشته شد',
            ]);
        }

        $product->likedUsers()->attach($user->id);
        $product->increment('likes');

        return response()->json([
            'message' => 'محصول لایک شد',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/products/purchase/{id}",
     *     summary="",
     *     description="",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(property="tracking_code", type="string", example=""),
     *             @OA\Property(
     *                 property="transaction",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example=""),
     *                 @OA\Property(property="type", type="string", example=""),
     *                 @OA\Property(property="coins", type="integer", example=-1),
     *                 @OA\Property(property="coins_before", type="integer", example=1),
     *                 @OA\Property(property="coins_after", type="integer", example=1),
     *                 @OA\Property(property="paid_amount", type="integer", example=1),
     *                 @OA\Property(property="description", type="string", example=""),
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="reference_code", type="string", example=""),
     *                 @OA\Property(property="wallet_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example=""),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example=""),
     *                 @OA\Property(property="id", type="integer", example=1)
     *             )
     *         )
     *     ),
     * )
     */
    public function purchase(int $id, Request $request): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'message' => 'محصول یافت نشد',
            ], 404);
        }

        if (! $product->is_active) {
            return response()->json([
                'message' => 'محصول در دسترس نیست',
            ], 400);
        }

        $user = $request->user();

        $hasPurchased = $product->purchasedUsers()->where('user_id', $user->id)->exists();

        if ($hasPurchased) {
            return response()->json([
                'message' => 'شما قبلاً این محصول را خریداری کرده‌اید',
            ], 400);
        }

        $wallet = $user->wallet;

        if (! $wallet) {
            return response()->json([
                'message' => 'کیف پول یافت نشد',
            ], 404);
        }

        if (! $wallet->hasCoins($product->price)) {
            return response()->json([
                'message' => 'موجودی کیف پول کافی نیست',
                'required_coins' => $product->price,
                'current_coins' => $wallet->coins,
            ], 400);
        }

        try {
            DB::beginTransaction();

            $referenceCode = 'NOTIN-RC-BOOK-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -6));

            $transaction = $wallet->purchaseProduct($product, $referenceCode);

            $product->purchasedUsers()->attach($user->id);

            $product->increment('purchased');

            DB::commit();

            return response()->json([
                'message' => 'محصول با موفقیت خریداری شد',
                'transaction' => $transaction,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'خطا در خرید محصول: '.$e->getMessage(),
            ], 500);
        }
    }
}
