<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $categories = $query->select('id', 'name', 'color')->paginate(10);

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

        return response()->json($category);
    }
}
