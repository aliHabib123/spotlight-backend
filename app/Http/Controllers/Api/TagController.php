<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class TagController extends Controller
{
    /**
     * Display a listing of the tags.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $cacheKey = 'tags_' . $request->input('type', 'all') . '_category_' . $request->input('category_id', 'all');
        
        return Cache::remember($cacheKey, 3600, function() use ($request) {
            $query = Tag::query();
            
            // Filter by type if specified
            if ($request->has('type') && $request->type != 'all') {
                $query->where('type', $request->type);
            }
            
            // Filter by category_id if specified
            if ($request->has('category_id') && $request->category_id != 'all') {
                $query->where('category_id', $request->category_id);
            }
            
            // Include spotlight counts if requested
            if ($request->input('with_count', false)) {
                $query->withCount('spotlights');
            }
            
            // Sort by popularity or alphabetically
            if ($request->input('sort', 'alpha') === 'popular') {
                $query->withCount('spotlights')
                      ->orderByDesc('spotlights_count')
                      ->orderBy('name');
            } else {
                $query->orderBy('name');
            }
            
            return $query->get();
        });
    }

    /**
     * Store a newly created tag.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', Tag::class);
        
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:50|unique:tags,name',
                'type' => 'required|string|in:amenity,feature,cuisine,audience,general',
                'color' => 'nullable|string|max:20',
            ]);
            
            $tag = Tag::create($validated);
            
            // Clear tags cache
            $this->clearTagsCache();
            
            return response()->json([
                'message' => 'Tag created successfully',
                'data' => $tag
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Display the specified tag.
     *
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function show(Tag $tag)
    {
        $this->authorize('view', $tag);
        
        $tag->loadCount('spotlights');
        
        return response()->json([
            'data' => $tag
        ]);
    }

    /**
     * Display spotlights with the specified tag.
     *
     * @param  \App\Models\Tag  $tag
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function spotlights(Tag $tag, Request $request)
    {
        $this->authorize('view', $tag);
        
        $spotlights = $tag->spotlights()
            ->with(['category', 'tags', 'location'])
            ->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));
        
        return response()->json([
            'data' => $spotlights
        ]);
    }

    /**
     * Update the specified tag.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tag $tag)
    {
        $this->authorize('update', $tag);
        
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:50|unique:tags,name,' . $tag->id,
                'type' => 'sometimes|string|in:amenity,feature,cuisine,audience,general',
                'color' => 'nullable|string|max:20',
            ]);
            
            $tag->update($validated);
            
            // Clear tags cache
            $this->clearTagsCache();
            
            return response()->json([
                'message' => 'Tag updated successfully',
                'data' => $tag
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Remove the specified tag from storage.
     *
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tag $tag)
    {
        $this->authorize('delete', $tag);
        
        // Check if tag is used in spotlights
        if ($tag->spotlights()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete tag that is in use',
            ], 422);
        }
        
        $tag->delete();
        
        // Clear tags cache
        $this->clearTagsCache();
        
        return response()->json([
            'message' => 'Tag deleted successfully'
        ]);
    }
    
    /**
     * Clear all tag-related cache.
     */
    protected function clearTagsCache()
    {
        Cache::forget('tags_all');
        Cache::forget('tags_amenity');
        Cache::forget('tags_feature');
        Cache::forget('tags_cuisine');
        Cache::forget('tags_audience');
        Cache::forget('tags_general');
    }
}
