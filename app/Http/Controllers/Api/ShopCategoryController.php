<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShopCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopCategoryController extends Controller
{
    /**
     * Get all shop categories.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = ShopCategory::query();
        
        // Filter by active status
        if (!$request->has('include_inactive') || !$request->include_inactive) {
            $query->where('is_active', true);
        }
        
        // Filter by parent_id (for subcategories)
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } else if ($request->has('top_level') && $request->top_level) {
            // If top_level is specified, only return categories with no parent
            $query->whereNull('parent_id');
        }
        
        // Order by display_order and then by name
        $query->orderBy('display_order')->orderBy('name');
        
        // Get categories with their shops count
        $categories = $query->withCount('shops')->get();
        
        // Check if hierarchical structure is requested
        if ($request->has('hierarchical') && $request->hierarchical) {
            // Only get top-level categories
            $categories = ShopCategory::where('is_active', true)
                ->whereNull('parent_id')
                ->withCount('shops')
                ->orderBy('display_order')
                ->orderBy('name')
                ->get();
                
            // Load children recursively
            $categories->load(['children' => function ($query) {
                $query->where('is_active', true)
                    ->withCount('shops')
                    ->orderBy('display_order')
                    ->orderBy('name');
            }]);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }
    
    /**
     * Get a specific shop category.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        // Find by ID or slug
        $category = is_numeric($id) 
            ? ShopCategory::findOrFail($id)
            : ShopCategory::where('slug', $id)->firstOrFail();
        
        // Load relationships
        $category->load([
            'parent',
            'children' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('display_order')
                    ->orderBy('name');
            }
        ]);
        
        // Get shops count
        $category->shops_count = $category->shops()->count();
        
        return response()->json([
            'status' => 'success',
            'data' => $category
        ]);
    }
    
    /**
     * Get shops in a specific category.
     *
     * @param  string  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function shops($id, Request $request): JsonResponse
    {
        // Find by ID or slug
        $category = is_numeric($id) 
            ? ShopCategory::findOrFail($id)
            : ShopCategory::where('slug', $id)->firstOrFail();
        
        // Get shops with pagination
        $perPage = $request->input('per_page', 10);
        $shops = $category->shops()
            ->where('is_active', true)
            ->orderBy('is_featured', 'desc')
            ->orderBy('display_order')
            ->orderBy('name')
            ->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $shops
        ]);
    }
}
