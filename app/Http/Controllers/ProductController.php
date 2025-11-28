<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
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
}
