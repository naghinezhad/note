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
     *     summary="",
     *     description="",
     *     tags={"Categories"},
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
     *                     @OA\Property(property="color", type="string", example=""),
     *                     @OA\Property(property="order", type="integer", example=1)
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
     *     summary="",
     *     description="",
     *     tags={"Categories"},
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
     *                     @OA\Property(property="name", type="string", example=""),
     *                     @OA\Property(property="color", type="string", example=""),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(
     *                         property="products",
     *                         type="array",
     *
     *                         @OA\Items(
     *                             type="object",
     *
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example=""),
     *                             @OA\Property(property="high_quality_image", type="string", example=""),
     *                             @OA\Property(property="low_quality_image", type="string", example=""),
     *                             @OA\Property(property="price", type="number", format="float", example=1),
     *                             @OA\Property(property="description", type="string", example=""),
     *                             @OA\Property(property="likes", type="integer", example=1),
     *                             @OA\Property(property="views", type="integer", example=1),
     *                             @OA\Property(property="purchased", type="integer", example=1),
     *                             @OA\Property(property="category_id", type="integer", example=1),
     *                             @OA\Property(property="is_active", type="boolean", example=true),
     *                             @OA\Property(property="is_3d", type="boolean", example=true),
     *                             @OA\Property(property="created_at", type="string", format="date-time", example=""),
     *                             @OA\Property(property="updated_at", type="string", format="date-time", example=""),
     *                             @OA\Property(property="is_free", type="boolean", example=true),
     *                             @OA\Property(property="is_purchased", type="boolean", example=true),
     *                             @OA\Property(property="is_liked", type="boolean", example=true)
     *                         )
     *                     )
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
     *     summary="",
     *     description="",
     *     tags={"Categories"},
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
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example=""),
     *             @OA\Property(property="color", type="string", example=""),
     *             @OA\Property(property="description", type="string", example=""),
     *             @OA\Property(property="order", type="integer", example=1),
     *             @OA\Property(property="created_at", type="string", format="date-time", example=""),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example=""),
     *             @OA\Property(property="total_likes", type="integer", example=1),
     *             @OA\Property(property="total_views", type="integer", example=1),
     *             @OA\Property(property="total_purchased", type="integer", example=1),
     *             @OA\Property(property="total_3d_products", type="integer", example=1),
     *             @OA\Property(property="total_paid_products", type="integer", example=1)
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
