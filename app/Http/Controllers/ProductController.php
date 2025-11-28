<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/products",
     *     summary="دریافت لیست محصولات",
     *     description="دریافت لیست تمام محصولات فعال با قابلیت جستجو، فیلتر و مرتب‌سازی",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="جستجو در نام و توضیحات محصول",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="لپ تاپ")
     *     ),
     *
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="فیلتر بر اساس دسته‌بندی",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="مرتب‌سازی محصولات (newest, oldest, most_liked, most_purchased, most_viewed, price_high, price_low)",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"newest", "oldest", "most_liked", "most_purchased", "most_viewed", "price_high", "price_low"}, example="newest")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="تعداد محصولات در هر صفحه",
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
     *         description="دریافت موفق لیست محصولات",
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
     *                     @OA\Property(property="name", type="string", example="لپ تاپ ایسوس"),
     *                     @OA\Property(property="description", type="string", example="لپ تاپ گیمینگ با مشخصات بالا"),
     *                     @OA\Property(property="price", type="number", format="float", example=15000000),
     *                     @OA\Property(property="category_id", type="integer", example=1),
     *                     @OA\Property(property="likes", type="integer", example=120),
     *                     @OA\Property(property="purchased", type="integer", example=45),
     *                     @OA\Property(property="views", type="integer", example=580),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="is_free", type="boolean", example=false),
     *                     @OA\Property(property="is_purchased", type="boolean", example=false),
     *                     @OA\Property(
     *                         property="category",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="الکترونیک"),
     *                         @OA\Property(property="color", type="string", example="#FF5733")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=100),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=10),
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
     *     summary="دریافت لیست خریدهای من",
     *     description="دریافت لیست محصولات خریداری شده توسط کاربر با قابلیت جستجو، فیلتر و مرتب‌سازی",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="جستجو در نام و توضیحات محصول",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="لپ تاپ")
     *     ),
     *
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="فیلتر بر اساس دسته‌بندی",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="مرتب‌سازی محصولات (newest, oldest, price_high, price_low)",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"newest", "oldest", "price_high", "price_low"}, example="newest")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="تعداد محصولات در هر صفحه",
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
     *         description="دریافت موفق لیست خریدها",
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
     *                     @OA\Property(property="name", type="string", example="لپ تاپ ایسوس"),
     *                     @OA\Property(property="description", type="string", example="لپ تاپ گیمینگ با مشخصات بالا"),
     *                     @OA\Property(property="price", type="number", format="float", example=15000000),
     *                     @OA\Property(property="category_id", type="integer", example=1),
     *                     @OA\Property(
     *                         property="pivot",
     *                         type="object",
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z")
     *                     ),
     *                     @OA\Property(
     *                         property="category",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="الکترونیک"),
     *                         @OA\Property(property="color", type="string", example="#FF5733")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=3),
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
            ->withPivot('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $products->items(),
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
     *     summary="دریافت اطلاعات یک محصول",
     *     description="دریافت اطلاعات کامل یک محصول بر اساس شناسه و ثبت بازدید",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="شناسه محصول",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="دریافت موفق اطلاعات محصول",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="لپ تاپ ایسوس"),
     *                 @OA\Property(property="description", type="string", example="لپ تاپ گیمینگ با مشخصات بالا"),
     *                 @OA\Property(property="price", type="number", format="float", example=15000000),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="likes", type="integer", example=120),
     *                 @OA\Property(property="purchased", type="integer", example=45),
     *                 @OA\Property(property="views", type="integer", example=581),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="is_free", type="boolean", example=false),
     *                 @OA\Property(property="is_purchased", type="boolean", example=false),
     *                 @OA\Property(
     *                     property="category",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="الکترونیک"),
     *                     @OA\Property(property="color", type="string", example="#FF5733")
     *                 )
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
     *         description="محصول یافت نشد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="محصول یافت نشد")
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
        $hasViewed = $product->viewedUsers()->where('user_id', $user->id)->exists();

        if (! $hasViewed) {
            $product->viewedUsers()->attach($user->id);
            $product->increment('views');
        }

        $productData = $product->toArray();
        $productData['is_free'] = $product->price == 0;
        $productData['is_purchased'] = $product->purchasedUsers()->where('user_id', $user->id)->exists();

        return response()->json([
            'data' => $productData,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/products/like/{id}",
     *     summary="لایک یا حذف لایک محصول",
     *     description="لایک کردن یا برداشتن لایک از یک محصول",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="شناسه محصول",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="عملیات موفق",
     *
     *         @OA\JsonContent(
     *             oneOf={
     *
     *                 @OA\Schema(
     *
     *                     @OA\Property(property="message", type="string", example="محصول لایک شد"),
     *                     @OA\Property(property="likes", type="integer", example=121)
     *                 ),
     *
     *                 @OA\Schema(
     *
     *                     @OA\Property(property="message", type="string", example="لایک برداشته شد"),
     *                     @OA\Property(property="likes", type="integer", example=120)
     *                 )
     *             }
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
     *         description="محصول یافت نشد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="محصول یافت نشد")
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
                'likes' => $product->likes,
            ]);
        }

        $product->likedUsers()->attach($user->id);
        $product->increment('likes');

        return response()->json([
            'message' => 'محصول لایک شد',
            'likes' => $product->likes,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/products/purchase/{id}",
     *     summary="خرید محصول",
     *     description="خریداری کردن یک محصول توسط کاربر",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="شناسه محصول",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="خرید موفق",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="محصول با موفقیت خریداری شد"),
     *             @OA\Property(property="purchased", type="integer", example=46)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="محصول قبلاً خریداری شده است",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="شما قبلاً این محصول را خریداری کرده‌اید")
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
     *         description="محصول یافت نشد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="محصول یافت نشد")
     *         )
     *     )
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

        $user = $request->user();
        $hasPurchased = $product->purchasedUsers()->where('user_id', $user->id)->exists();

        if ($hasPurchased) {
            return response()->json([
                'message' => 'شما قبلاً این محصول را خریداری کرده‌اید',
            ], 400);
        }

        $product->purchasedUsers()->attach($user->id);
        $product->increment('purchased');

        return response()->json([
            'message' => 'محصول با موفقیت خریداری شد',
            'purchased' => $product->purchased,
        ]);
    }
}
