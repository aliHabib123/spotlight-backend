<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get all products with optional filtering.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();
        
        // Filter by active status
        if (!$request->has('include_inactive') || !$request->include_inactive) {
            $query->where('is_active', true);
        }
        
        // Filter by featured status
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }
        
        // Filter by shop
        if ($request->has('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }
        
        // Filter by shop category
        if ($request->has('category_id')) {
            $categoryId = $request->category_id;
            $query->whereHas('shop', function ($q) use ($categoryId) {
                $q->whereHas('categories', function ($q2) use ($categoryId) {
                    $q2->where('shop_categories.id', $categoryId);
                });
            });
        }
        
        // Filter by product type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        
        // Filter by on sale
        if ($request->has('on_sale')) {
            $query->where('is_on_sale', $request->boolean('on_sale'));
        }
        
        // Search by name or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }
        
        // Order by featured, then by name
        $query->orderBy('is_featured', 'desc')
            ->orderBy('name');
        
        // Eager load relationships
        $query->with(['shop', 'variations']);
        
        // Paginate results
        $perPage = $request->input('per_page', 12);
        $products = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }
    
    /**
     * Get a specific product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        // Find by ID or slug
        $product = is_numeric($id) 
            ? Product::findOrFail($id)
            : Product::where('slug', $id)->firstOrFail();
        
        // Load relationships
        $product->load(['shop', 'variations']);
        
        // Check if product is active
        if (!$product->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product is not available'
            ], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $product
        ]);
    }
    
    /**
     * Get products by shop category.
     *
     * @param  string  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function byCategory($id, Request $request): JsonResponse
    {
        // Find category by ID or slug
        $category = is_numeric($id) 
            ? ShopCategory::findOrFail($id)
            : ShopCategory::where('slug', $id)->firstOrFail();
        
        // Get shop IDs in this category
        $shopIds = $category->shops()->where('shops.is_active', true)->pluck('shops.id');
        
        // Build products query
        $query = Product::whereIn('shop_id', $shopIds);
        
        // Filter by active status
        if (!$request->has('include_inactive') || !$request->include_inactive) {
            $query->where('is_active', true);
        }
        
        // Apply other filters
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        
        if ($request->has('on_sale')) {
            $query->where('is_on_sale', $request->boolean('on_sale'));
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }
        
        // Order by featured, then by name
        $query->orderBy('is_featured', 'desc')
            ->orderBy('name');
        
        // Eager load relationships
        $query->with(['shop', 'variations']);
        
        // Paginate results
        $perPage = $request->input('per_page', 12);
        $products = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'category' => $category,
            'data' => $products
        ]);
    }
}
