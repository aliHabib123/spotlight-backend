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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
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

        // Filter by is_published
        $query->where('is_published', true);

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

        // Filter by is_featured
        if ($request->has('is_featured')) {
            $query->where('is_featured', filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN));
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

        return response()->json($query->paginate($request->input('per_page', 15)));
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

        $paginator = Cache::remember($cacheKey, 3600, function() use ($request) {
            return Spotlight::with(['category', 'tags', 'location'])
                ->where('is_featured', true)
                // Filter by is_published
                ->where('is_published', true)
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 8));
        });

        return response()->json($paginator);
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

        $paginator = Cache::remember($cacheKey, 3600, function() use ($request) {
            return Spotlight::with(['category', 'tags', 'location'])
                ->where('is_trending', true)
                // Filter by is_published
                ->where('is_published', true)
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 8));
        });

        return response()->json($paginator);
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

        $paginator = Spotlight::with(['category', 'tags', 'location'])
            ->whereIn('category_id', $categoryIds)
            // Filter by is_published
            ->where('is_published', true)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($paginator);
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
            'video_provider' => 'nullable|string|in:youtube,vimeo,self',
            'video_url' => 'nullable|string',
            'video_file' => [
                'nullable',
                'required_if:video_provider,self',
                'file',
                'mimes:mp4,mov,avi,wmv',
                'max:102400', // 100MB max file size
            ],
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'attributes' => 'nullable|array',
        ]);

        try {
            return DB::transaction(function() use ($validated, $request) {
                // Create spotlight
                // Handle video upload for self-hosted videos
                $videoUrl = null;
                // Handle video provider and URL
                if (isset($validated['video_provider'])) {
                    if ($validated['video_provider'] === 'self') {
                        if ($request->hasFile('video_file')) {
                            // Handle direct file upload from API
                            $videoPath = $request->file('video_file')->store('spotlight-videos', 'public');
                            $validated['video_url'] = asset('storage/' . $videoPath);
                        }
                        // For Filament admin panel uploads, the video_url is already set correctly
                        // because we're using the video_url field directly in the form
                    }
                    // For YouTube, Vimeo, etc. the video_url is already set in the form
                }

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
                    'video_provider' => $validated['video_provider'] ?? null,
                    'video_url' => $videoUrl,
                    'user_id' => Auth::id(),
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

        // For published spotlights, anyone can view
        // For unpublished ones, check permission
        if (!$spotlight->is_published) {
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
            'video_provider' => 'nullable|string|in:youtube,vimeo,self',
            'video_url' => 'nullable|string',
            'video_file' => [
                'nullable',
                'required_if:video_provider,self',
                'file',
                'mimes:mp4,mov,avi,wmv',
                'max:102400', // 100MB max file size
            ],
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'attributes' => 'nullable|array',
        ]);

        try {
            return DB::transaction(function() use ($validated, $request, $spotlight) {
                // Handle video upload for self-hosted videos
                // Handle video provider and URL for update
                if (isset($validated['video_provider'])) {
                    if ($validated['video_provider'] === 'self') {
                        // Check if we need to remove old video file when uploading a new one
                        // Only do this for API uploads or if the video URL has changed
                        $newVideoUrl = $validated['video_url'] ?? null;
                        if ($spotlight->video_provider === 'self' && $spotlight->video_url &&
                            ($request->hasFile('video_file') ||
                             ($newVideoUrl && $newVideoUrl !== $spotlight->video_url))) {

                            $oldPath = str_replace(asset('storage/'), '', $spotlight->video_url);
                            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                            }
                        }

                        if ($request->hasFile('video_file')) {
                            // Handle direct file upload from API
                            $videoPath = $request->file('video_file')->store('spotlight-videos', 'public');
                            $validated['video_url'] = asset('storage/' . $videoPath);
                        }
                        // For Filament admin panel uploads, the video_url is already set correctly
                        // because we're using the video_url field directly in the form

                        // Debug log to see what's happening
                        \Illuminate\Support\Facades\Log::debug('Video update data', [
                            'video_provider' => $validated['video_provider'],
                            'video_url' => $validated['video_url'] ?? null
                        ]);
                    }
                    // For other providers, video_url is already set in the form
                }

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
            'is_published' => true,
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
            'is_published' => false,
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
