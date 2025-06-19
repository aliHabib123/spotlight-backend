<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SpotlightCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SpotlightCategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // If location slug is provided, use the byLocation method instead
        if ($request->has('location')) {
            return $this->byLocation($request, $request->input('location'));
        }
        
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
        Gate::authorize('create', SpotlightCategory::class);
        
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
    public function show(SpotlightCategory $category)
    {
        return response()->json($category->load('parent', 'attributeDefinitions'));
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
        Gate::authorize('update', $category);
        
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:spotlight_categories,id',
                'is_active' => 'boolean',
                'icon' => 'nullable|string|max:50',
                'display_order' => 'nullable|integer|min:0',
                'attribute_definitions' => 'nullable|array',
                'attribute_definitions.*' => 'exists:spotlight_attribute_definitions,id',
            ]);
            
            // Prevent circular parent-child relationship
            if (isset($validated['parent_id']) && $validated['parent_id'] == $category->id) {
                return response()->json([
                    'message' => 'A category cannot be its own parent',
                ], 422);
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
                'data' => $category->load('parent', 'attributeDefinitions')
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        }
    }
    
    /**
     * Remove the specified category.
     *
     * @param  \App\Models\SpotlightCategory  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(SpotlightCategory $category)
    {
        Gate::authorize('delete', $category);
        
        // Check if category has children or linked spotlights
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
     * @return array
     */
    private function buildCategoryTree($categories, $parentId = null)
    {
        $tree = [];
        
        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $children = $this->buildCategoryTree($categories, $category->id);
                if ($children) {
                    $category->children = $children;
                }
                $tree[] = $category;
            }
        }
        
        return $tree;
    }
    
    /**
     * Get all attributes associated with a category.
     *
     * @param  \App\Models\SpotlightCategory  $category
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function attributes(SpotlightCategory $category, Request $request)
    {
        $cacheKey = 'spotlight_category_attributes_' . $category->id;
        
        return Cache::remember($cacheKey, 3600, function() use ($category, $request) {
            $query = $category->attributeDefinitions()
                ->with('options')
                ->orderBy('pivot_display_order', 'asc');
            
            // Filter by type if requested
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            
            // Include parent category attributes if requested
            if ($request->input('include_parent', false) && $category->parent_id) {
                $parentAttributes = $category->parent->attributeDefinitions()
                    ->with('options')
                    ->orderBy('pivot_display_order', 'asc');
                
                if ($request->has('type')) {
                    $parentAttributes->where('type', $request->type);
                }
                
                // Merge parent attributes with category attributes
                return $query->get()->merge($parentAttributes->get());
            }
            
            return $query->get();
        });
    }
    
    /**
     * Get all filterable attributes for a category to be used as filters.
     *
     * @param  \App\Models\SpotlightCategory  $category
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function filters(SpotlightCategory $category, Request $request)
    {
        $cacheKey = 'spotlight_category_filters_' . $category->id;
        
        return Cache::remember($cacheKey, 3600, function() use ($category, $request) {
            // Get category attributes that are filterable
            $filterableAttributes = $category->attributeDefinitions()
                ->with('options')
                ->where('is_filterable', true)
                ->orderBy('pivot_display_order', 'asc');
            
            // Include parent category filterable attributes if requested
            if ($request->input('include_parent', false) && $category->parent_id) {
                $parentFilters = $category->parent->attributeDefinitions()
                    ->with('options')
                    ->where('is_filterable', true)
                    ->orderBy('pivot_display_order', 'asc');
                
                $combined = $filterableAttributes->get()->merge($parentFilters->get());
                
                // Format filter options
                return $this->formatFilters($combined);
            }
            
            // Format filter options
            return $this->formatFilters($filterableAttributes->get());
        });
    }
    
    /**
     * Format attributes as filters with their available options.
     *
     * @param  \Illuminate\Support\Collection  $attributes
     * @return array
     */
    private function formatFilters($attributes)
    {
        $filters = [];
        
        foreach ($attributes as $attribute) {
            $filter = [
                'id' => $attribute->id,
                'key' => $attribute->key,
                'name' => $attribute->name,
                'type' => $attribute->type,
                'display_type' => $attribute->display_type ?? null,
                'options' => []
            ];
            
            // Include options for attributes that have them
            // This covers select, multiselect, enum with options
            if (in_array($attribute->type, ['select', 'multiselect', 'enum']) || 
                (isset($attribute->display_type) && in_array($attribute->display_type, ['select', 'multiselect']))) {
                $filter['options'] = $attribute->options->map(function($option) {
                    return [
                        'id' => $option->id,
                        'value' => $option->value,
                        'label' => $option->label ?: $option->value // Fall back to value if label is null
                    ];
                });
            }
            
            $filters[] = $filter;
        }
        
        return $filters;
    }
    
    /**
     * Attach attributes to a category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SpotlightCategory  $category
     * @return \Illuminate\Http\Response
     */
    public function attachAttributes(Request $request, SpotlightCategory $category)
    {
        Gate::authorize('update', $category);
        
        $validated = $request->validate([
            'attributes' => 'required|array',
            'attributes.*' => 'exists:spotlight_attribute_definitions,id',
            'display_orders' => 'array',
            'display_orders.*' => 'integer|min:0',
        ]);
        
        $syncData = [];
        
        foreach ($validated['attributes'] as $index => $attributeId) {
            $syncData[$attributeId] = [
                'display_order' => isset($validated['display_orders'][$index]) 
                    ? $validated['display_orders'][$index] 
                    : 0,
            ];
        }
        
        $category->attributeDefinitions()->syncWithoutDetaching($syncData);
        
        // Clear cache
        Cache::forget('spotlight_category_attributes_' . $category->id);
        Cache::forget('spotlight_category_filters_' . $category->id);
        
        return response()->json([
            'message' => 'Attributes attached to category successfully',
            'data' => $category->load('attributeDefinitions')
        ]);
    }
    
    /**
     * Detach an attribute from a category.
     *
     * @param  \App\Models\SpotlightCategory  $category
     * @param  int  $attribute
     * @return \Illuminate\Http\Response
     */
    public function detachAttribute(SpotlightCategory $category, $attribute)
    {
        Gate::authorize('update', $category);
        
        $category->attributeDefinitions()->detach($attribute);
        
        // Clear cache
        Cache::forget('spotlight_category_attributes_' . $category->id);
        Cache::forget('spotlight_category_filters_' . $category->id);
        
        return response()->json([
            'message' => 'Attribute detached from category successfully',
        ]);
    }
    
    /**
     * Update the display order of an attribute in a category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SpotlightCategory  $category
     * @param  int  $attribute
     * @return \Illuminate\Http\Response
     */
    public function updateAttributeOrder(Request $request, SpotlightCategory $category, $attribute)
    {
        Gate::authorize('update', $category);
        
        $validated = $request->validate([
            'display_order' => 'required|integer|min:0',
        ]);
        
        $category->attributeDefinitions()->updateExistingPivot(
            $attribute, 
            ['display_order' => $validated['display_order']]
        );
        
        // Clear cache
        Cache::forget('spotlight_category_attributes_' . $category->id);
        Cache::forget('spotlight_category_filters_' . $category->id);
        
        return response()->json([
            'message' => 'Attribute order updated successfully',
        ]);
    }
    
    /**
     * Get categories by home screen location slug.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $locationSlug
     * @return \Illuminate\Http\JsonResponse
     */
    public function byLocation(Request $request, string $locationSlug)
    {
        $cacheKey = 'spotlight_categories_location_' . $locationSlug . '_' . $request->input('include_inactive', false);
        
        return Cache::remember($cacheKey, 3600, function() use ($request, $locationSlug) {
            $query = SpotlightCategory::query()
                ->whereHas('homeScreenLocation', function($query) use ($locationSlug) {
                    $query->where('slug', $locationSlug);
                })
                ->with('homeScreenLocation');
            
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
            
            return response()->json([
                'status' => 'success',
                'location' => $locationSlug,
                'categories' => $categories
            ]);
        });
    }
}
