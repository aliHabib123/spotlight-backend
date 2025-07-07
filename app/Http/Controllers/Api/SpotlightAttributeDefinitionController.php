<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SpotlightAttributeDefinition;
use App\Models\SpotlightAttributeOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SpotlightAttributeDefinitionController extends Controller
{
    /**
     * Display a listing of the attribute definitions.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = SpotlightAttributeDefinition::query()
            ->with(['options' => function($query) {
                // Only include options that don't have parent-child relationships
                $query->whereNull('parent_option_id');
            }])
            ->orderBy('name');
            
        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by category
        if ($request->has('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('spotlight_categories.id', $request->category_id);
            });
        }
        
        // Exclude attributes with parent-child relationships
        $query->whereNull('parent_id')
              ->whereDoesntHave('children');
            
        return response()->json($query->paginate($request->input('per_page', 25)));
    }
    
    /**
     * Display the specified attribute definition.
     *
     * @param SpotlightAttributeDefinition $attribute
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(SpotlightAttributeDefinition $attribute)
    {
        return response()->json($attribute->load('options', 'categories'));
    }
    
    /**
     * Get hierarchical filter data for a category, optimized for frontend filtering.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHierarchicalFilters(Request $request)
    {
        $categoryId = $request->category_id;
        
        if (!$categoryId) {
            return response()->json(['error' => 'Category ID is required'], 400);
        }
        
        // Use caching for performance
        $cacheKey = "hierarchical_filters_{$categoryId}_" . md5(json_encode($request->all()));
        
        return response()->json(Cache::remember($cacheKey, now()->addHour(), function () use ($categoryId) {
            // Get all attribute definitions for this category with eager loading
            $attributeDefinitions = SpotlightAttributeDefinition::whereHas('categories', function ($query) use ($categoryId) {
                $query->where('spotlight_categories.id', $categoryId);
            })
            ->with([
                'options' => function($query) {
                    $query->orderBy('display_order');
                }
            ])
            ->whereNull('parent_id') // Only get parent attributes
            // Only include attributes that have children or whose options have child options
            ->where(function($query) {
                $query->has('children')
                      ->orWhereHas('options', function($q) {
                          $q->has('childOptions');
                      });
            })
            ->orderBy('display_order')
            ->get();
            
            // Format the response with recursive loading of children
            return $attributeDefinitions->map(function($definition) {
                return $this->formatDefinitionWithChildren($definition);
            });
        }));
    }
    
    /**
     * Format an attribute definition with its children and options.
     *
     * @param SpotlightAttributeDefinition $definition
     * @return array
     */
    /**
     * Format an attribute definition with all its children recursively.
     * This ensures that parent-child relationships are maintained at all levels.
     *
     * @param SpotlightAttributeDefinition $definition
     * @return array
     */
    private function formatDefinitionWithChildren(SpotlightAttributeDefinition $definition)
    {
        // Load children if not already loaded
        if (!$definition->relationLoaded('children')) {
            $definition->load([
                'children' => function($query) {
                    $query->orderBy('display_order');
                },
                'children.options' => function($query) {
                    $query->orderBy('display_order');
                }
            ]);
        }
        
        $formattedOptions = $definition->options->map(function($option) {
            return [
                'id' => $option->id,
                'label' => $option->display_label,
                'value' => $option->value,
                'color' => $option->color,
            ];
        })->toArray();
        
        $formattedChildren = $definition->children->map(function($child) {
            // Load options with their parent_option_id for this child
            $childOptions = $child->options->map(function($option) {
                return [
                    'id' => $option->id,
                    'label' => $option->display_label,
                    'value' => $option->value,
                    'parent_option_id' => $option->parent_option_id,
                    'color' => $option->color,
                ];
            })->toArray();
            
            // Base data for this child
            $childData = [
                'id' => $child->id,
                'name' => $child->name,
                'slug' => $child->slug,
                'type' => $child->type,
                'is_filterable' => $child->is_filterable,
                'options' => $childOptions
            ];
            
            // Get mapping of parent options to their IDs for use in children
            $optionIdMapping = collect($childOptions)->pluck('id')->all();
            
            // Check if this child has its own children (grandchildren)
            $grandchildren = SpotlightAttributeDefinition::where('parent_id', $child->id)
                ->with([
                    'options' => function($query) {
                        $query->orderBy('display_order');
                    }
                ])
                ->orderBy('display_order')
                ->get();
                
            if ($grandchildren->isNotEmpty()) {
                $childData['children'] = $grandchildren->map(function($grandchild) use ($child, $optionIdMapping) {
                    // Get this grandchild's data
                    $grandchildData = [
                        'id' => $grandchild->id,
                        'name' => $grandchild->name,
                        'slug' => $grandchild->slug,
                        'type' => $grandchild->type,
                        'is_filterable' => $grandchild->is_filterable,
                    ];
                    
                    // Handle the options for the grandchild, ensuring parent_option_id is set
                    $grandchildOptions = $grandchild->options->map(function($option) use ($optionIdMapping) {
                        // Ensure we correctly set parent_option_id if not already set
                        return [
                            'id' => $option->id,
                            'label' => $option->display_label,
                            'value' => $option->value,
                            'parent_option_id' => $option->parent_option_id,
                            'color' => $option->color,
                        ];
                    })->toArray();
                    
                    $grandchildData['options'] = $grandchildOptions;
                    
                    // Check for great-grandchildren
                    $greatGrandchildren = SpotlightAttributeDefinition::where('parent_id', $grandchild->id)
                        ->with(['options' => function($query) {
                            $query->orderBy('display_order');
                        }])
                        ->orderBy('display_order')
                        ->get();
                        
                    if ($greatGrandchildren->isNotEmpty()) {
                        $grandchildData['children'] = $greatGrandchildren->map(function($greatGrandchild) {
                            // Recursive call for deeper levels
                            return $this->formatDefinitionWithChildren($greatGrandchild);
                        })->toArray();
                    } else {
                        $grandchildData['children'] = [];
                    }
                    
                    return $grandchildData;
                })->toArray();
            }
            
            return $childData;
        })->toArray();
        
        return [
            'id' => $definition->id,
            'name' => $definition->name,
            'slug' => $definition->slug,
            'type' => $definition->type,
            'is_filterable' => $definition->is_filterable,
            'options' => $formattedOptions,
            'children' => $formattedChildren,
        ];
    }
    

    
    /**
     * Store a newly created attribute definition.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('manage attributes');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:100|unique:spotlight_attribute_definitions',
            'description' => 'nullable|string',
            'type' => 'required|string|in:text,textarea,number,select,multiselect,boolean,date,datetime',
            'is_required' => 'boolean',
            'default_value' => 'nullable|string',
            'validation_rules' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'options' => 'nullable|array',
            'options.*.value' => 'required|string|max:255',
            'options.*.label' => 'required|string|max:255',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:spotlight_categories,id'
        ]);
        
        // Generate slug if key not provided
        if (!isset($validated['key']) || empty($validated['key'])) {
            $validated['key'] = Str::slug($validated['name']);
        }
        
        // Create the attribute definition
        $attribute = SpotlightAttributeDefinition::create([
            'name' => $validated['name'],
            'key' => $validated['key'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'is_required' => $validated['is_required'] ?? false,
            'default_value' => $validated['default_value'] ?? null,
            'validation_rules' => $validated['validation_rules'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
        ]);
        
        // Create options if provided
        if (isset($validated['options']) && is_array($validated['options'])) {
            foreach ($validated['options'] as $optionData) {
                $attribute->options()->create([
                    'value' => $optionData['value'],
                    'label' => $optionData['label'],
                ]);
            }
        }
        
        // Attach to categories if provided
        if (isset($validated['categories']) && is_array($validated['categories'])) {
            $attribute->categories()->attach($validated['categories']);
        }
        
        // Clear cache
        Cache::forget('spotlight_attributes');
        
        return response()->json([
            'message' => 'Attribute definition created successfully',
            'data' => $attribute->load('options', 'categories')
        ], 201);
    }
    
    /**
     * Update the specified attribute definition.
     *
     * @param Request $request
     * @param SpotlightAttributeDefinition $attribute
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, SpotlightAttributeDefinition $attribute)
    {
        $this->authorize('manage attributes');
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'key' => 'sometimes|required|string|max:100|unique:spotlight_attribute_definitions,key,' . $attribute->id,
            'description' => 'nullable|string',
            'type' => 'sometimes|required|string|in:text,textarea,number,select,multiselect,boolean,date,datetime',
            'is_required' => 'boolean',
            'default_value' => 'nullable|string',
            'validation_rules' => 'nullable|string',
            'display_order' => 'nullable|integer',
        ]);
        
        // Update the attribute
        $attribute->update($validated);
        
        // Clear cache
        Cache::forget('spotlight_attributes');
        
        return response()->json([
            'message' => 'Attribute definition updated successfully',
            'data' => $attribute->load('options', 'categories')
        ]);
    }
    
    /**
     * Remove the specified attribute definition.
     *
     * @param SpotlightAttributeDefinition $attribute
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(SpotlightAttributeDefinition $attribute)
    {
        $this->authorize('manage attributes');
        
        // Check if attribute has values
        $hasValues = $attribute->values()->exists();
        if ($hasValues) {
            return response()->json([
                'message' => 'Cannot delete attribute definition because it has associated values',
            ], 409);
        }
        
        // Delete options first
        $attribute->options()->delete();
        
        // Detach from categories
        $attribute->categories()->detach();
        
        // Delete the attribute
        $attribute->delete();
        
        // Clear cache
        Cache::forget('spotlight_attributes');
        
        return response()->json([
            'message' => 'Attribute definition deleted successfully',
        ]);
    }
    
    /**
     * Store a new option for the specified attribute.
     *
     * @param Request $request
     * @param SpotlightAttributeDefinition $attribute
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeOption(Request $request, SpotlightAttributeDefinition $attribute)
    {
        $this->authorize('manage attributes');
        
        if (!in_array($attribute->type, ['select', 'multiselect'])) {
            return response()->json([
                'message' => 'Options can only be added to select or multiselect attributes',
            ], 422);
        }
        
        $validated = $request->validate([
            'value' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'display_order' => 'nullable|integer',
        ]);
        
        $option = $attribute->options()->create([
            'value' => $validated['value'],
            'label' => $validated['label'],
            'display_order' => $validated['display_order'] ?? 0,
        ]);
        
        // Clear cache
        Cache::forget('spotlight_attributes');
        
        return response()->json([
            'message' => 'Option created successfully',
            'data' => $option
        ], 201);
    }
    
    /**
     * Update the specified option.
     *
     * @param Request $request
     * @param SpotlightAttributeOption $option
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOption(Request $request, SpotlightAttributeOption $option)
    {
        $this->authorize('manage attributes');
        
        $validated = $request->validate([
            'value' => 'sometimes|required|string|max:255',
            'label' => 'sometimes|required|string|max:255',
            'display_order' => 'nullable|integer',
        ]);
        
        $option->update($validated);
        
        // Clear cache
        Cache::forget('spotlight_attributes');
        
        return response()->json([
            'message' => 'Option updated successfully',
            'data' => $option
        ]);
    }
    
    /**
     * Delete the specified option.
     *
     * @param SpotlightAttributeOption $option
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteOption(SpotlightAttributeOption $option)
    {
        $this->authorize('manage attributes');
        
        // Check if option is being used
        $hasValues = $option->attributeDefinition->values()
            ->where('attribute_option_id', $option->id)
            ->exists();
            
        if ($hasValues) {
            return response()->json([
                'message' => 'Cannot delete option because it is in use',
            ], 409);
        }
        
        $option->delete();
        
        // Clear cache
        Cache::forget('spotlight_attributes');
        
        return response()->json([
            'message' => 'Option deleted successfully',
        ]);
    }
}
