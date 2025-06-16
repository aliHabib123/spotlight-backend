<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Spotlight;
use App\Models\SpotlightCategory;
use App\Models\SpotlightAttributeValue;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class SpotlightController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the spotlights.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Spotlight::query()
            ->with(['category', 'tags', 'location']);
            
        // Filter by is_active if that column exists
        if (Schema::hasColumn('spotlights', 'is_active')) {
            $query->where('is_active', true);
        }
        
        // Apply filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->has('tag_id')) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->where('tags.id', $request->tag_id);
            });
        }
        
        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }
        
        // Filter by is_trending
        if ($request->has('is_trending')) {
            $query->where('is_trending', filter_var($request->is_trending, FILTER_VALIDATE_BOOLEAN));
        }
        
        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Custom attributes filter
        if ($request->has('attributes') && is_array($request->attributes)) {
            foreach ($request->attributes as $key => $value) {
                $query->whereHas('attributeValues', function($q) use ($key, $value) {
                    $q->whereHas('attributeDefinition', function($sq) use ($key) {
                        $sq->where('key', $key);
                    })
                    ->where(function($sq) use ($value) {
                        $sq->where('value', $value)
                          ->orWhereHas('attributeOption', function($osq) use ($value) {
                              $osq->where('value', $value);
                          });
                    });
                });
            }
        }
        
        // Sorting
        $sortField = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        return $query->paginate($request->input('per_page', 15));
    }

    /**
     * Display featured spotlights.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function featured(Request $request)
    {
        $cacheKey = 'featured_spotlights_' . $request->input('per_page', 8);
        
        return Cache::remember($cacheKey, 3600, function() use ($request) {
            return Spotlight::with(['category', 'tags', 'location'])
                ->where('is_featured', true)
                // Filter by is_active if that column exists
                ->when(Schema::hasColumn('spotlights', 'is_active'), function($query) {
                    return $query->where('is_active', true);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 8));
        });
    }
    
    /**
     * Display trending spotlights.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function trending(Request $request)
    {
        $cacheKey = 'trending_spotlights_' . $request->input('per_page', 8);
        
        return Cache::remember($cacheKey, 3600, function() use ($request) {
            return Spotlight::with(['category', 'tags', 'location'])
                ->where('is_trending', true)
                // Filter by is_active if that column exists
                ->when(Schema::hasColumn('spotlights', 'is_active'), function($query) {
                    return $query->where('is_active', true);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 8));
        });
    }

    /**
     * Display spotlights by category.
     *
     * @param  \App\Models\SpotlightCategory  $category
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function byCategory(SpotlightCategory $category, Request $request)
    {
        $categoryIds = [$category->id];
        
        // Include child categories if requested
        if ($request->input('include_children', false)) {
            $children = $category->getAllChildren();
            $categoryIds = array_merge($categoryIds, $children->pluck('id')->toArray());
        }
        
        return Spotlight::with(['category', 'tags', 'location'])
            ->whereIn('category_id', $categoryIds)
            // Filter by is_active if that column exists (assuming spotlights have an active state)
            ->when(Schema::hasColumn('spotlights', 'is_active'), function($query) {
                return $query->where('is_active', true);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));
    }

    /**
     * Store a newly created spotlight.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', Spotlight::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:spotlight_categories,id',
            'location_id' => 'nullable|exists:locations,id',
            'is_active' => 'nullable|boolean',
            'rating' => 'nullable|numeric|min:0|max:5',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'website_url' => 'nullable|url',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'attributes' => 'nullable|array',
        ]);
        
        try {
            return DB::transaction(function() use ($validated, $request) {
                // Create spotlight
                $spotlight = Spotlight::create([
                    'name' => $validated['name'],
                    'description' => $validated['description'],
                    'category_id' => $validated['category_id'],
                    'location_id' => $validated['location_id'] ?? null,
                    'is_active' => $validated['is_active'] ?? false,
                    'rating' => $validated['rating'] ?? null,
                    'contact_email' => $validated['contact_email'] ?? null,
                    'contact_phone' => $validated['contact_phone'] ?? null,
                    'website_url' => $validated['website_url'] ?? null,
                    'user_id' => auth()->id(),
                ]);
                
                // Sync tags
                if (isset($validated['tags'])) {
                    $spotlight->tags()->sync($validated['tags']);
                }
                
                // Process custom attributes
                if (isset($validated['attributes']) && is_array($validated['attributes'])) {
                    $this->processAttributes($spotlight, $validated['attributes']);
                }
                
                // Handle media uploads
                if ($request->hasFile('media')) {
                    foreach ($request->file('media') as $mediaFile) {
                        $path = $mediaFile->store('spotlight-media', 'public');
                        $spotlight->media()->create([
                            'file_path' => $path,
                            'type' => 'image',
                            'is_featured' => false,
                            'display_order' => 0,
                        ]);
                    }
                }
                
                return response()->json([
                    'message' => 'Spotlight created successfully',
                    'data' => $spotlight->load(['category', 'tags', 'location', 'attributeValues', 'media'])
                ], 201);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the spotlight',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified spotlight.
     *
     * @param  \App\Models\Spotlight  $spotlight
     * @return \Illuminate\Http\Response
     */
    public function show(Spotlight $spotlight)
    {
        $this->authorize('view', $spotlight);
        
        // For active spotlights, anyone can view
        // For inactive ones, check permission
        if (!$spotlight->is_active) {
            $this->authorize('manage', $spotlight);
        }
        
        return response()->json([
            'data' => $spotlight->load([
                'category', 
                'tags', 
                'location', 
                'attributeValues.attributeDefinition',
                'attributeValues.attributeOption',
                'media'
            ])
        ]);
    }

    /**
     * Update the specified spotlight.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Spotlight  $spotlight
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Spotlight $spotlight)
    {
        $this->authorize('update', $spotlight);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category_id' => 'sometimes|exists:spotlight_categories,id',
            'location_id' => 'nullable|exists:locations,id',
            'is_active' => 'sometimes|boolean',
            'rating' => 'nullable|numeric|min:0|max:5',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'website_url' => 'nullable|url',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'attributes' => 'nullable|array',
        ]);
        
        try {
            return DB::transaction(function() use ($validated, $request, $spotlight) {
                // Update spotlight
                $spotlight->update($validated);
                
                // Sync tags if provided
                if (isset($validated['tags'])) {
                    $spotlight->tags()->sync($validated['tags']);
                }
                
                // Process custom attributes if provided
                if (isset($validated['attributes']) && is_array($validated['attributes'])) {
                    $this->processAttributes($spotlight, $validated['attributes']);
                }
                
                return response()->json([
                    'message' => 'Spotlight updated successfully',
                    'data' => $spotlight->fresh(['category', 'tags', 'location', 'attributeValues', 'media'])
                ]);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the spotlight',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified spotlight from storage.
     *
     * @param  \App\Models\Spotlight  $spotlight
     * @return \Illuminate\Http\Response
     */
    public function destroy(Spotlight $spotlight)
    {
        $this->authorize('delete', $spotlight);
        
        try {
            DB::transaction(function() use ($spotlight) {
                // Delete related attribute values
                $spotlight->attributeValues()->delete();
                
                // Delete spotlight
                $spotlight->delete();
            });
            
            return response()->json([
                'message' => 'Spotlight deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the spotlight',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Publish a spotlight.
     *
     * @param  \App\Models\Spotlight  $spotlight
     * @return \Illuminate\Http\Response
     */
    public function publish(Spotlight $spotlight)
    {
        $this->authorize('publish', $spotlight);
        
        $spotlight->update([
            'is_active' => true,
            'published_at' => now(),
        ]);
        
        return response()->json([
            'message' => 'Spotlight published successfully',
            'data' => $spotlight->fresh()
        ]);
    }
    
    /**
     * Unpublish a spotlight.
     *
     * @param  \App\Models\Spotlight  $spotlight
     * @return \Illuminate\Http\Response
     */
    public function unpublish(Spotlight $spotlight)
    {
        $this->authorize('publish', $spotlight);
        
        $spotlight->update([
            'is_active' => false,
        ]);
        
        return response()->json([
            'message' => 'Spotlight unpublished successfully',
            'data' => $spotlight->fresh()
        ]);
    }
    
    /**
     * Mark a spotlight as featured.
     *
     * @param  \App\Models\Spotlight  $spotlight
     * @return \Illuminate\Http\Response
     */
    public function feature(Spotlight $spotlight)
    {
        $this->authorize('feature', $spotlight);
        
        $spotlight->update([
            'is_featured' => true,
        ]);
        
        // Clear featured cache
        Cache::forget('featured_spotlights_8');
        
        return response()->json([
            'message' => 'Spotlight marked as featured',
            'data' => $spotlight->fresh()
        ]);
    }
    
    /**
     * Remove featured status from a spotlight.
     *
     * @param  \App\Models\Spotlight  $spotlight
     * @return \Illuminate\Http\Response
     */
    public function unfeature(Spotlight $spotlight)
    {
        $this->authorize('feature', $spotlight);
        
        $spotlight->update([
            'is_featured' => false,
        ]);
        
        // Clear featured cache
        Cache::forget('featured_spotlights_8');
        
        return response()->json([
            'message' => 'Spotlight removed from featured',
            'data' => $spotlight->fresh()
        ]);
    }
    
    /**
     * Process custom attributes for a spotlight.
     *
     * @param  \App\Models\Spotlight  $spotlight
     * @param  array  $attributesData
     * @return void
     */
    protected function processAttributes(Spotlight $spotlight, array $attributesData)
    {
        // First get all definitions for this category
        $category = SpotlightCategory::with('attributeDefinitions')->find($spotlight->category_id);
        $definitions = $category->attributeDefinitions->keyBy('id');
        
        // Delete existing attribute values to avoid duplicates
        $spotlight->attributeValues()->delete();
        
        // Create new attribute values
        foreach ($attributesData as $definitionId => $value) {
            if (!isset($definitions[$definitionId])) {
                continue; // Skip if not valid for this category
            }
            
            $definition = $definitions[$definitionId];
            
            if ($definition->type === 'enum' && !empty($value)) {
                // For enum types, store the option ID
                $spotlight->attributeValues()->create([
                    'attribute_definition_id' => $definitionId,
                    'attribute_option_id' => $value,
                ]);
            } elseif (!empty($value)) {
                // For other types, store the direct value
                $spotlight->attributeValues()->create([
                    'attribute_definition_id' => $definitionId,
                    'value' => $value,
                ]);
            }
        }
    }
}
