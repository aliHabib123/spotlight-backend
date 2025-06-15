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
            ->with('options')
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
