<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\BannerLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Generate a cache key based on all request parameters
        $cacheKey = 'banners_index_' . md5(json_encode($request->all()));
        
        // Cache for 1 hour (3600 seconds)
        $result = Cache::remember($cacheKey, 3600, function() use ($request) {
            $query = Banner::with(['location', 'user:id,name']);
            
            // Filter by location
            if ($request->has('location_id')) {
                $query->where('banner_location_id', $request->location_id);
            } elseif ($request->has('location_slug')) {
                $location = BannerLocation::where('slug', $request->location_slug)->first();
                if ($location) {
                    $query->where('banner_location_id', $location->id);
                }
            }
            
            // Filter by active status
            if ($request->has('active') && $request->boolean('active')) {
                $query->active();
            }
            
            // Order by display_order
            return $query->orderBy('display_order')->paginate(15);
        });
        
        return response()->json([
            'status' => 'success',
            'data' => $result
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Check if user is authenticated
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Validate request
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'banner_location_id' => 'required|exists:banner_locations,id',
            'url' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'image' => 'required|image|max:2048' // Max 2MB
        ]);
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $validated['image_path'] = $path;
        }
        
        // Add user_id
        $validated['user_id'] = $user->id;
        
        // Create banner
        $banner = Banner::create($validated);
        
        // Load relationships
        $banner->load(['location', 'user:id,name']);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Banner created successfully',
            'data' => $banner
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $banner = Banner::with(['location', 'user:id,name'])->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $banner
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        // Check if user is authenticated
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $banner = Banner::findOrFail($id);
        
        // Check if user owns this banner or is admin
        if ($banner->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to update this banner'
            ], 403);
        }
        
        // Validate request
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'banner_location_id' => 'sometimes|required|exists:banner_locations,id',
            'url' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'image' => 'nullable|image|max:2048' // Max 2MB
        ]);
        
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($banner->image_path) {
                Storage::disk('public')->delete($banner->image_path);
            }
            
            // Store new image
            $path = $request->file('image')->store('banners', 'public');
            $validated['image_path'] = $path;
        }
        
        // Update banner
        $banner->update($validated);
        
        // Load relationships
        $banner->load(['location', 'user:id,name']);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Banner updated successfully',
            'data' => $banner
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        // Check if user is authenticated
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $banner = Banner::findOrFail($id);
        
        // Check if user owns this banner or is admin
        if ($banner->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to delete this banner'
            ], 403);
        }
        
        // Delete image if it exists
        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }
        
        // Delete banner
        $banner->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Banner deleted successfully'
        ]);
    }
    
    /**
     * Get banners by location slug.
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function byLocation(string $slug): JsonResponse
    {
        $location = BannerLocation::where('slug', $slug)->firstOrFail();
        
        $banners = $location->activeBanners()->with('user:id,name')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'location' => $location,
                'banners' => $banners
            ]
        ]);
    }
}
