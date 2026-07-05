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
        // Generate a cache key based on all request parameters
        $cacheKey = 'spotlights_index_' . md5(json_encode($request->all()));

        // Cache for 1 hour (3600 seconds)
        return Cache::remember($cacheKey, 3600, function() use ($request) {
            // Enable query logging
            DB::enableQueryLog();

        $query = Spotlight::query()
            ->with(['category', 'tags', 'location', 'locations']);

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
            // Match against the many-to-many locations pivot. The pivot is
            // backfilled from the legacy single location_id, so this also covers
            // spotlights that only have a primary location set.
            $query->whereHas('locations', function($q) use ($request) {
                $q->where('locations.id', $request->location_id);
            });
        }

        // Filter by is_trending
        if ($request->has('is_trending')) {
            $query->where('is_trending', filter_var($request->is_trending, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by is_featured
        if ($request->has('is_featured')) {
            $query->where('is_featured', filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('is_category_featured')) {
            $query->where('is_category_featured', filter_var($request->is_category_featured, FILTER_VALIDATE_BOOLEAN));
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
        if ($request->has('attributes') && is_array($request->input('attributes'))) {
            $attributesInput = $request->input('attributes');

            foreach ($attributesInput as $key => $value) {
                // Add this to detailed logs
                \Illuminate\Support\Facades\Log::info("Processing attribute filter: {$key} = {$value}");

                // First, check if the attribute definition exists with the given name or slug
                $attrDefQuery = DB::table('spotlight_attribute_definitions')
                    ->where('slug', $key)
                    ->orWhere('name', $key);

                // Log the SQL
                \Illuminate\Support\Facades\Log::info("Attribute definition check SQL: " . $attrDefQuery->toSql());

                $attrDef = $attrDefQuery->first();

                // If attribute definition doesn't exist, return empty results
                // if (!$attrDef) {
                //     \Illuminate\Support\Facades\Log::info("Attribute '{$key}' not found - returning empty results");
                //     // This forces an empty result set
                //     $query->whereRaw('1 = 0');
                //     return response()->json([]);
                // }

                // Check if any spotlights have this attribute with this value
                $attributeValueQuery = DB::table('spotlight_attribute_values')
                    ->where('attribute_definition_id', $attrDef->id)
                    ->where(function($q) use ($value) {
                        $q->where('value', $value)
                          ->orWhereExists(function($sq) use ($value) {
                              $sq->select(DB::raw(1))
                                 ->from('spotlight_attribute_options')
                                 ->whereColumn('spotlight_attribute_options.id', 'spotlight_attribute_values.attribute_option_id')
                                 ->where('spotlight_attribute_options.value', $value);
                          });
                    });

                // Log the value check SQL
                \Illuminate\Support\Facades\Log::info("Value check SQL: " . $attributeValueQuery->toSql());

                // $valueExists = $attributeValueQuery->exists();

                // If the value doesn't exist for any spotlight, return empty results
                // if (!$valueExists) {
                //     \Illuminate\Support\Facades\Log::info("Value '{$value}' not found for attribute '{$key}' - returning empty results");
                //     // This forces an empty result set
                //     $query->whereRaw('1 = 0');
                //     return response()->json([]);
                // }

                // If we get here, both the attribute and value exist, so apply the filter
                $query->whereHas('attributeValues', function($q) use ($attrDef, $value) {
                    $q->where('attribute_definition_id', $attrDef->id)
                      ->where(function($sq) use ($value) {
                        $sq->where('value', $value)
                          ->orWhereHas('attributeOption', function($optionQ) use ($value) {
                              $optionQ->where('value', $value);
                          });
                    });
                });
            }
        }

        // Sorting
        $sortField = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $result = $query->paginate($request->input('per_page', 15));

        // Add user rating for each spotlight (null if not authenticated or not rated)
        foreach ($result->items() as $spotlight) {
            $userRating = null;
            if (Auth::check()) {
                $userRating = $spotlight->ratings()
                    ->where('user_id', Auth::id())
                    ->first();
            }
            $spotlight->user_rating = $userRating;
        }

        // Log queries to storage for debugging
        $log = [
            'request_all' => $request->all(),
            'request_input' => $request->input(),
            'has_attributes' => $request->has('attributes'),
            'attributes_input' => $request->input('attributes'),
            'query_string' => $request->getQueryString(),
            'server_query_string' => $_SERVER['QUERY_STRING'] ?? null,
            'queries' => DB::getQueryLog(),
        ];

        // Store in public directory for easier access
        Storage::disk('public')->put('spotlight_filter_log.json', json_encode($log, JSON_PRETTY_PRINT));

        // Log to Laravel log file
        \Illuminate\Support\Facades\Log::info('Spotlight Filter Debug', ['data' => $log]);

        return response()->json($result);
        });
    }

    /**
     * Display featured spotlights.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function featured(Request $request)
    {
        $cacheKey = 'featured_spotlights_sorted_by_display_order_' . $request->input('per_page', 25);

        $paginator = Cache::remember($cacheKey, 3600, function() use ($request) {
            return Spotlight::with(['category', 'tags', 'location', 'locations'])
                ->where('is_featured', true)
                // Filter by is_published
                ->where('is_published', true)
                ->orderBy('display_order', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 25));
        });

        // Add user rating for each spotlight (null if not authenticated or not rated)
        foreach ($paginator->items() as $spotlight) {
            $userRating = null;
            if (Auth::check()) {
                $userRating = $spotlight->ratings()
                    ->where('user_id', Auth::id())
                    ->first();
            }
            $spotlight->user_rating = $userRating;
        }

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
            return Spotlight::with(['category', 'tags', 'location', 'locations'])
                ->where('is_trending', true)
                // Filter by is_published
                ->where('is_published', true)
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 8));
        });

        // Add user rating for each spotlight (null if not authenticated or not rated)
        foreach ($paginator->items() as $spotlight) {
            $userRating = null;
            if (Auth::check()) {
                $userRating = $spotlight->ratings()
                    ->where('user_id', Auth::id())
                    ->first();
            }
            $spotlight->user_rating = $userRating;
        }

        return response()->json($paginator);
    }

    /**
     * Display the latest 10 spotlights from all categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function latest()
    {
        $cacheKey = 'latest_10_spotlights';

        $spotlights = Cache::remember($cacheKey, 3600, function() {
            return Spotlight::with(['category', 'tags', 'location', 'locations'])
                ->where('is_published', true)
                ->where('hide_from_latest', false)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        });

        // Add user rating for each spotlight (null if not authenticated or not rated)
        foreach ($spotlights as $spotlight) {
            $userRating = null;
            if (Auth::check()) {
                $userRating = $spotlight->ratings()
                    ->where('user_id', Auth::id())
                    ->first();
            }
            $spotlight->user_rating = $userRating;
        }

        return response()->json($spotlights);
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
        // Generate a cache key based on category and request parameters
        $cacheKey = 'spotlights_category_' . $category->id . '_' . md5(json_encode($request->all()));

        // Cache for 1 hour (3600 seconds)
        $paginator = Cache::remember($cacheKey, 3600, function() use ($category, $request) {
            $categoryIds = [$category->id];

            // Include child categories if requested
            if ($request->input('include_children', false)) {
                $children = $category->getAllChildren();
                $categoryIds = array_merge($categoryIds, $children->pluck('id')->toArray());
            }

            $query = Spotlight::with(['category', 'tags', 'location', 'locations'])
                ->whereIn('category_id', $categoryIds)
                // Filter by is_published
                ->where('is_published', true);

            if ($request->has('is_featured')) {
                $query->where('is_featured', filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('is_category_featured')) {
                $query->where('is_category_featured', filter_var($request->is_category_featured, FILTER_VALIDATE_BOOLEAN));
            }

            $paginator = $query
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            // Add user rating for each spotlight (null if not authenticated or not rated)
            foreach ($paginator->items() as $spotlight) {
                $userRating = null;
                if (Auth::check()) {
                    $userRating = $spotlight->ratings()
                        ->where('user_id', Auth::id())
                        ->first();
                }
                $spotlight->user_rating = $userRating;
            }

            return $paginator;
        });

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
            'location_ids' => 'nullable|array',
            'location_ids.*' => 'exists:locations,id',
            'is_active' => 'nullable|boolean',
            'rating' => 'nullable|numeric|min:0|max:5',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'phone_number' => 'nullable|string|max:20',
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

                // Sync locations (many-to-many). Accepts a `location_ids` array,
                // and falls back to the single `location_id` for older clients.
                $this->syncLocations($spotlight, $validated);

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

                // Clear latest spotlights cache
                Cache::forget('latest_10_spotlights');

                return response()->json([
                    'message' => 'Spotlight created successfully',
                    'data' => $spotlight->load(['category', 'tags', 'location', 'locations', 'attributeValues', 'media'])
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
        // Only check authorization for unpublished spotlights
        if (!$spotlight->is_published) {
            // Return 404 for guests trying to access unpublished spotlights
            if (Auth::guest()) {
                abort(404, 'Spotlight not found');
            }

            // For authenticated users, check if they have permission to manage spotlights
            $this->authorize('manage', $spotlight);
        }

        // Generate a cache key based on spotlight ID and user authentication status
        $userId = Auth::check() ? Auth::id() : 'guest';
        $cacheKey = 'spotlight_detail_' . $spotlight->id . '_' . $userId;

        // Cache for 1 hour (3600 seconds)
        $result = Cache::remember($cacheKey, 3600, function() use ($spotlight) {
            // Load ratings with user information
            $spotlight->load([
                'category',
                'tags',
                'location',
                'locations',
                'attributeValues.attributeDefinition',
                'attributeValues.attributeOption',
                'media'
            ]);

            // Check if user is logged in and has rated this spotlight
            $userRating = null;
            if (Auth::check()) {
                $userRating = $spotlight->ratings()
                    ->where('user_id', Auth::id())
                    ->first();
            }

            // Store the data we want to return
            return [
                'data' => $spotlight,
                'user_rating' => $userRating
            ];
        });

        return response()->json($result);
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
            'location_ids' => 'nullable|array',
            'location_ids.*' => 'exists:locations,id',
            'is_active' => 'sometimes|boolean',
            'rating' => 'nullable|numeric|min:0|max:5',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'phone_number' => 'nullable|string|max:20',
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

                // Sync locations if location data was provided
                if (array_key_exists('location_ids', $validated) || array_key_exists('location_id', $validated)) {
                    $this->syncLocations($spotlight, $validated);
                }

                // Process custom attributes if provided
                if (isset($validated['attributes']) && is_array($validated['attributes'])) {
                    $this->processAttributes($spotlight, $validated['attributes']);
                }

                return response()->json([
                    'message' => 'Spotlight updated successfully',
                    'data' => $spotlight->fresh(['category', 'tags', 'location', 'locations', 'attributeValues', 'media'])
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

                // Clear latest spotlights cache
                Cache::forget('latest_10_spotlights');
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

        // Clear latest spotlights cache
        Cache::forget('latest_10_spotlights');

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

        // Clear latest spotlights cache
        Cache::forget('latest_10_spotlights');

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
        Cache::forget('featured_spotlights_sorted_by_display_order_8');

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
        Cache::forget('featured_spotlights_sorted_by_display_order_8');

        return response()->json([
            'message' => 'Spotlight removed from featured',
            'data' => $spotlight->fresh()
        ]);
    }

    /**
     * Sync the many-to-many locations for a spotlight and keep the legacy
     * single `location_id` column pointing at the first location for backward
     * compatibility with older app versions.
     *
     * Accepts either a `location_ids` array (new clients) or a single
     * `location_id` (older clients / Filament).
     *
     * @param  \App\Models\Spotlight  $spotlight
     * @param  array  $data
     * @return void
     */
    protected function syncLocations(Spotlight $spotlight, array $data): void
    {
        // Prefer the explicit array; otherwise fall back to the single id.
        if (array_key_exists('location_ids', $data) && is_array($data['location_ids'])) {
            $locationIds = $data['location_ids'];
        } elseif (! empty($data['location_id'])) {
            $locationIds = [$data['location_id']];
        } else {
            $locationIds = [];
        }

        // Normalise to unique integers, preserving order.
        $locationIds = array_values(array_unique(array_map('intval', $locationIds)));

        $spotlight->locations()->sync($locationIds);

        // Keep the primary location_id column in sync (first location, or null).
        $spotlight->location_id = $locationIds[0] ?? null;
        $spotlight->save();
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
