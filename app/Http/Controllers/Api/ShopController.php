<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * Get all shops with optional filtering.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Shop::query();
        
        // Filter by active status
        if (!$request->has('include_inactive') || !$request->include_inactive) {
            $query->where('is_active', true);
        }
        
        // Filter by featured status
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }
        
        // Filter by category
        if ($request->has('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('shop_categories.id', $request->category_id);
            });
        }
        
        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        // Order by featured, display_order, and name
        $query->orderBy('is_featured', 'desc')
            ->orderBy('display_order')
            ->orderBy('name');
        
        // Eager load relationships
        $query->with('categories');
        
        // Paginate results
        $perPage = $request->input('per_page', 10);
        $shops = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $shops
        ]);
    }
    
    /**
     * Get a specific shop.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        // Find by ID or slug
        $shop = is_numeric($id) 
            ? Shop::findOrFail($id)
            : Shop::where('slug', $id)->firstOrFail();
        
        // Load relationships
        $shop->load('categories');
        
        // Get products count
        $shop->products_count = $shop->products()->count();
        
        return response()->json([
            'status' => 'success',
            'data' => $shop
        ]);
    }
    
    /**
     * Get products for a specific shop.
     *
     * @param  string  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function products($id, Request $request): JsonResponse
    {
        // Find by ID or slug
        $shop = is_numeric($id) 
            ? Shop::findOrFail($id)
            : Shop::where('slug', $id)->firstOrFail();
        
        // Build products query
        $query = $shop->products();
        
        // Filter by active status
        if (!$request->has('include_inactive') || !$request->include_inactive) {
            $query->where('is_active', true);
        }
        
        // Filter by featured status
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
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
        
        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        // Order by featured, then by name
        $query->orderBy('is_featured', 'desc')
            ->orderBy('name');
        
        // Eager load variations for variable products
        $query->with('variations');
        
        // Paginate results
        $perPage = $request->input('per_page', 12);
        $products = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }
}
