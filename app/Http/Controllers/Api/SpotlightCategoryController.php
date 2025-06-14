<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SpotlightCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class SpotlightCategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $cacheKey = 'spotlight_categories_' . $request->input('include_inactive', false);
        
        return Cache::remember($cacheKey, 3600, function() use ($request) {
            $query = SpotlightCategory::query();
            
            // Only include active categories for public view
            if (!$request->input('include_inactive', false)) {
                $query->where('is_active', true);
            }
            
            // Include parent relationship for hierarchical structure
            $query->with('parent');
            
            // Include attribute definitions if requested
            if ($request->input('with_attributes', false)) {
                $query->with('attributeDefinitions');
            }
            
            $categories = $query->orderBy('display_order')->get();
            
            // Transform to hierarchical structure if requested
            if ($request->input('hierarchical', false)) {
                return $this->buildCategoryTree($categories);
            }
            
            return $categories;
        });
    }
    
    /**
     * Store a newly created category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', SpotlightCategory::class);
        
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:spotlight_categories,id',
                'is_active' => 'boolean',
                'icon' => 'nullable|string|max:50',
                'display_order' => 'nullable|integer|min:0',
                'attribute_definitions' => 'nullable|array',
                'attribute_definitions.*' => 'exists:spotlight_attribute_definitions,id',
            ]);
            
            $category = SpotlightCategory::create($validated);
            
            // Sync attribute definitions if provided
            if (isset($validated['attribute_definitions'])) {
                $category->attributeDefinitions()->sync($validated['attribute_definitions']);
            }
            
            // Clear categories cache
            Cache::forget('spotlight_categories_0');
            Cache::forget('spotlight_categories_1');
            
            return response()->json([
                'message' => 'Category created successfully',
                'data' => $category->load('parent', 'attributeDefinitions')
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        }
    }
    
    /**
     * Display the specified category.
     *
     * @param  \App\Models\SpotlightCategory  $category
     * @return \Illuminate\Http\Response
     */
    public function show(SpotlightCategory $category, Request $request)
    {
        $this->authorize('view', $category);
        
        $data = $category->load('parent');
        
        // Include attribute definitions if requested
        if ($request->input('with_attributes', false)) {
            $data->load('attributeDefinitions');
        }
        
        // Include children if requested
        if ($request->input('with_children', false)) {
            $data->load('children');
        }
        
        // Include spotlight count if requested
        if ($request->input('with_count', false)) {
            $data->loadCount('spotlights');
        }
        
        return response()->json([
            'data' => $data
        ]);
    }
    
    /**
     * Update the specified category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SpotlightCategory  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SpotlightCategory $category)
    {
        $this->authorize('update', $category);
        
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:spotlight_categories,id',
                'is_active' => 'boolean',
                'icon' => 'nullable|string|max:50',
                'display_order' => 'nullable|integer|min:0',
                'attribute_definitions' => 'nullable|array',
                'attribute_definitions.*' => 'exists:spotlight_attribute_definitions,id',
            ]);
            
            // Check that parent isn't set to self or one of its descendants
            if (isset($validated['parent_id']) && $validated['parent_id'] != null) {
                if ($validated['parent_id'] == $category->id) {
                    return response()->json([
                        'message' => 'Category cannot be its own parent',
                    ], 422);
                }
                
                $descendants = $category->getAllChildren()->pluck('id')->toArray();
                if (in_array($validated['parent_id'], $descendants)) {
                    return response()->json([
                        'message' => 'Category cannot have one of its descendants as its parent',
                    ], 422);
                }
            }
            
            $category->update($validated);
            
            // Sync attribute definitions if provided
            if (isset($validated['attribute_definitions'])) {
                $category->attributeDefinitions()->sync($validated['attribute_definitions']);
            }
            
            // Clear categories cache
            Cache::forget('spotlight_categories_0');
            Cache::forget('spotlight_categories_1');
            
            return response()->json([
                'message' => 'Category updated successfully',
                'data' => $category->fresh(['parent', 'attributeDefinitions'])
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        }
    }
    
    /**
     * Remove the specified category from storage.
     *
     * @param  \App\Models\SpotlightCategory  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(SpotlightCategory $category)
    {
        $this->authorize('delete', $category);
        
        // Check if category has children or spotlights
        if ($category->children()->count() > 0 || $category->spotlights()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with children or spotlights',
            ], 422);
        }
        
        $category->delete();
        
        // Clear categories cache
        Cache::forget('spotlight_categories_0');
        Cache::forget('spotlight_categories_1');
        
        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
    
    /**
     * Build a hierarchical tree of categories.
     *
     * @param  \Illuminate\Support\Collection  $categories
     * @param  int|null  $parentId
     * @return \Illuminate\Support\Collection
     */
    protected function buildCategoryTree($categories, $parentId = null)
    {
        $tree = collect();
        
        foreach ($categories as $category) {
            if ($category->parent_id === $parentId) {
                $children = $this->buildCategoryTree($categories, $category->id);
                
                if ($children->isNotEmpty()) {
                    $category->children = $children;
                }
                
                $tree->push($category);
            }
        }
        
        return $tree;
    }
}
