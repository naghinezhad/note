<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/categories",
     *     summary="دریافت لیست دسته‌بندی‌ها",
     *     description="دریافت لیست تمام دسته‌بندی‌ها با قابلیت جستجو و صفحه‌بندی",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="جستجو در نام دسته‌بندی",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="برنامه نویسی")
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
     *         description="دریافت موفق لیست دسته‌بندی‌ها",
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
     *                     @OA\Property(property="name", type="string", example="برنامه نویسی"),
     *                     @OA\Property(property="color", type="string", example="#FF5733")
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
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->select('id', 'name', 'color', 'order')->orderBy('order')->paginate(10);

        return response()->json([
            'data' => $categories->items(),
            'pagination' => [
                'total' => $categories->total(),
                'per_page' => $categories->perPage(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/categories/with-products",
     *     summary="دریافت لیست دسته‌بندی‌ها همراه با محصولات",
     *     description="دریافت لیست تمام دسته‌بندی‌ها همراه با 20 محصول اول هر دسته‌بندی (بدون صفحه‌بندی)",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="دریافت موفق لیست دسته‌بندی‌ها همراه با محصولات",
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
     *                     @OA\Property(property="name", type="string", example="الکترونیک"),
     *                     @OA\Property(property="color", type="string", example="#FF5733"),
     *                     @OA\Property(
     *                         property="products",
     *                         type="array",
     *
     *                         @OA\Items(
     *                             type="object",
     *
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="لپ تاپ ایسوس"),
     *                             @OA\Property(property="description", type="string", example="لپ تاپ گیمینگ با مشخصات بالا"),
     *                             @OA\Property(property="price", type="number", format="float", example=15000000),
     *                             @OA\Property(property="likes", type="integer", example=120),
     *                             @OA\Property(property="purchased", type="integer", example=45),
     *                             @OA\Property(property="views", type="integer", example=580),
     *                             @OA\Property(property="is_active", type="boolean", example=true),
     *                             @OA\Property(property="is_free", type="boolean", example=false),
     *                             @OA\Property(property="is_purchased", type="boolean", example=false),
     *                             @OA\Property(property="high_quality_image", type="string", example="https://example.com/signed-url"),
     *                             @OA\Property(property="low_quality_image", type="string", example="https://example.com/signed-url")
     *                         )
     *                     )
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
     *     )
     * )
     */
    public function categoriesWithProducts(Request $request): JsonResponse
    {
        $user = $request->user();

        $categories = Category::select('id', 'name', 'color', 'order')
            ->with(['products' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('created_at', 'desc')
                    ->limit(20);
            }])
            ->orderBy('order')
            ->get();

        $categoriesData = $categories->map(function ($category) use ($user) {
            $categoryArray = $category->toArray();

            $categoryArray['products'] = $category->products->map(function ($product) use ($user) {
                $productArray = $product->toArray();
                $productArray['is_free'] = $product->price == 0;
                $productArray['is_purchased'] = $user ? $product->purchasedUsers()->where('user_id', $user->id)->exists() : false;

                $productArray['high_quality_image'] = URL::temporarySignedRoute(
                    'signed.file',
                    now()->addYear(),
                    ['path' => $product->high_quality_image]
                );

                $productArray['low_quality_image'] = URL::temporarySignedRoute(
                    'signed.file',
                    now()->addYear(),
                    ['path' => $product->low_quality_image]
                );

                return $productArray;
            })->toArray();

            return $categoryArray;
        });

        return response()->json([
            'data' => $categoriesData,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/categories/{id}",
     *     summary="دریافت اطلاعات یک دسته‌بندی",
     *     description="دریافت اطلاعات کامل یک دسته‌بندی بر اساس شناسه",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="شناسه دسته‌بندی",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="دریافت موفق اطلاعات دسته‌بندی",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="برنامه نویسی"),
     *             @OA\Property(property="color", type="string", example="#FF5733"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T08:00:00.000000Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="دسته‌بندی یافت نشد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="دسته بندی یافت نشد")
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
    public function show($id): JsonResponse
    {
        $category = Category::find($id);

        if (! $category) {
            return response()->json([
                'message' => 'دسته بندی یافت نشد',
            ], 404);
        }

        $categoryArray = $category->toArray();

        $stats = DB::table('products')
            ->where('category_id', $category->id)
            ->selectRaw('
                COALESCE(SUM(likes), 0) as total_likes,
                COALESCE(SUM(views), 0) as total_views,
                COALESCE(SUM(purchased), 0) as total_purchased,
                COALESCE(SUM(CASE WHEN is_3d = 1 THEN 1 ELSE 0 END), 0) as total_3d_products,
                COALESCE(SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END), 0) as total_paid_products
            ')
            ->first();

        $categoryArray['total_likes'] = (int) $stats->total_likes;
        $categoryArray['total_views'] = (int) $stats->total_views;
        $categoryArray['total_purchased'] = (int) $stats->total_purchased;
        $categoryArray['total_3d_products'] = (int) $stats->total_3d_products;
        $categoryArray['total_paid_products'] = (int) $stats->total_paid_products;

        return response()->json($categoryArray);
    }
}
