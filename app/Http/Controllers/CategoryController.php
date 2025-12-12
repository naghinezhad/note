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
     *     summary="دریافت لیست دسته بندی‌ها",
     *     description="بازیابی لیست تمام دسته بندی‌ها با امکان جستجو",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="جستجو بر اساس نام دسته بندی",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="مدل‌های سه بعدی")
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
     *         description="لیست دسته بندی‌ها دریافت شد",
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
     *                     @OA\Property(property="name", type="string", example="مدل‌های سه بعدی"),
     *                     @OA\Property(property="color", type="string", example="#FF5733", description="رنگ دسته بندی"),
     *                     @OA\Property(property="order", type="integer", example=1, description="ترتیب نمایش")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=15),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=2),
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
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $categories = Category::select('id', 'name', 'color', 'order')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->input('search').'%');
            })
            ->orderBy('order')
            ->paginate(10);

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
     *     summary="دریافت دسته بندی‌ها با محصولات",
     *     description="بازیابی دسته بندی‌ها و محصولات فعال آن‌ها (حداکثر 20 محصول برای هر دسته)",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="جستجو در نام و توضیحات محصولات",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="تصویر")
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="مرتب سازی محصولات",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="string",
     *             enum={"newest", "oldest", "most_liked", "most_purchased", "most_viewed", "price_high", "price_low"},
     *             example="newest"
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="فیلتر بر اساس دسته بندی خاص",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="دسته بندی‌ها با محصولات دریافت شد",
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
     *                     @OA\Property(property="name", type="string", example="مدل‌های سه بعدی"),
     *                     @OA\Property(property="color", type="string", example="#FF5733"),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(
     *                         property="products",
     *                         type="array",
     *                         description="محصولات مرتب شده بر اساس ترجیح کاربر (خریداری + لایک شده محبوب‌تر است)",
     *
     *                         @OA\Items(
     *                             type="object",
     *
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="تصویر سه بعدی"),
     *                             @OA\Property(property="high_quality_image", type="string", example="https://example.com/storage/images/product-1-hq.jpg?signature=xxx"),
     *                             @OA\Property(property="low_quality_image", type="string", example="https://example.com/storage/images/product-1-lq.jpg?signature=xxx"),
     *                             @OA\Property(property="price", type="integer", example=100),
     *                             @OA\Property(property="description", type="string", example="توضیح محصول"),
     *                             @OA\Property(property="likes", type="integer", example=25),
     *                             @OA\Property(property="views", type="integer", example=150),
     *                             @OA\Property(property="purchased", type="integer", example=10),
     *                             @OA\Property(property="category_id", type="integer", example=1),
     *                             @OA\Property(property="is_active", type="boolean", example=true),
     *                             @OA\Property(property="is_3d", type="boolean", example=true),
     *                             @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
     *                             @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
     *                             @OA\Property(property="is_free", type="boolean", example=false),
     *                             @OA\Property(property="is_purchased", type="boolean", example=false),
     *                             @OA\Property(property="is_liked", type="boolean", example=false)
     *                         )
     *                     )
     *                 )
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
     *     )
     * )
     */
    public function categoriesWithProducts(Request $request): JsonResponse
    {
        $user = $request->user();

        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'newest');
        $categoryFilter = $request->get('category_id');

        $categories = Category::select('id', 'name', 'color', 'order')
            ->when($categoryFilter, fn ($q) => $q->where('id', $categoryFilter))
            ->with([
                'products' => function ($query) use ($user, $search, $sortBy) {

                    $query->where('is_active', true)
                        ->withExists([
                            'purchasedUsers as is_purchased' => fn ($q) => $user
                                ? $q->where('user_id', $user->id)
                                : $q->whereNull('user_id'),

                            'likedUsers as is_liked' => fn ($q) => $user
                                ? $q->where('user_id', $user->id)
                                : $q->whereNull('user_id'),
                        ]);

                    if ($search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('description', 'LIKE', "%{$search}%");
                        });
                    }

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

                    $query->limit(20);
                },
            ])
            ->orderBy('order')
            ->get();

        $rank = function ($p) {
            if ($p['is_purchased'] && $p['is_liked'] && $p['price'] > 0) {
                return 7;
            }
            if ($p['is_purchased'] && $p['is_liked'] && $p['price'] == 0) {
                return 6;
            }
            if ($p['is_purchased']) {
                return 5;
            }
            if ($p['is_liked'] && $p['price'] > 0) {
                return 4;
            }
            if ($p['is_liked'] && $p['price'] == 0) {
                return 3;
            }
            if ($p['price'] > 0) {
                return 2;
            }

            return 1;
        };

        $categoriesData = $categories->map(function ($category) use ($rank) {

            $productsFormatted = $category->products->map(function ($product) {

                $productArray = $product->toArray();

                $productArray['is_free'] = $product->price == 0;
                $productArray['is_purchased'] = $product->is_purchased ?? false;
                $productArray['is_liked'] = $product->is_liked ?? false;

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

            return [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color,
                'order' => $category->order,
                'products' => $productsFormatted->sortByDesc($rank)->values(),
            ];
        });

        return response()->json([
            'data' => $categoriesData,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/categories/{id}",
     *     summary="دریافت جزئیات دسته بندی",
     *     description="بازیابی اطلاعات کامل یک دسته بندی و آمار محصولات آن",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="شناسه دسته بندی",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="اطلاعات دسته بندی دریافت شد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="مدل‌های سه بعدی"),
     *             @OA\Property(property="color", type="string", example="#FF5733"),
     *             @OA\Property(property="description", type="string", example="توضیح دسته بندی"),
     *             @OA\Property(property="order", type="integer", example=1),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
     *             @OA\Property(property="total_likes", type="integer", example=500, description="کل لایک‌های تمام محصولات"),
     *             @OA\Property(property="total_views", type="integer", example=5000, description="کل مشاهدات تمام محصولات"),
     *             @OA\Property(property="total_purchased", type="integer", example=100, description="کل خریدهای تمام محصولات"),
     *             @OA\Property(property="total_3d_products", type="integer", example=25, description="تعداد محصولات سه بعدی"),
     *             @OA\Property(property="total_paid_products", type="integer", example=30, description="تعداد محصولات پولی")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="دسته بندی پیدا نشد",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="دسته بندی یافت نشد")
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
