<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BannerLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BannerLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $locations = BannerLocation::withCount('banners')
            ->orderBy('name')
            ->get();
            
        return response()->json([
            'status' => 'success',
            'data' => $locations
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'size' => 'nullable|string|max:50',
            'is_active' => 'boolean'
        ]);
        
        // Generate slug from name
        $validated['slug'] = Str::slug($validated['name']);
        
        // Create banner location
        $location = BannerLocation::create($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Banner location created successfully',
            'data' => $location
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
        // Find by ID or slug
        $location = is_numeric($id) 
            ? BannerLocation::findOrFail($id)
            : BannerLocation::where('slug', $id)->firstOrFail();
        
        // Get active banners for this location
        $banners = $location->activeBanners()->with('user:id,name')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'location' => $location,
                'banners' => $banners
            ]
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
        
        // Find by ID or slug
        $location = is_numeric($id) 
            ? BannerLocation::findOrFail($id)
            : BannerLocation::where('slug', $id)->firstOrFail();
        
        // Validate request
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'size' => 'nullable|string|max:50',
            'is_active' => 'boolean'
        ]);
        
        // Update slug if name is changed
        if (isset($validated['name']) && $validated['name'] !== $location->name) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        
        // Update banner location
        $location->update($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Banner location updated successfully',
            'data' => $location
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
        
        // Find by ID or slug
        $location = is_numeric($id) 
            ? BannerLocation::findOrFail($id)
            : BannerLocation::where('slug', $id)->firstOrFail();
        
        // Check if location has banners
        if ($location->banners()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete location with associated banners'
            ], 422);
        }
        
        // Delete banner location
        $location->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Banner location deleted successfully'
        ]);
    }
}
