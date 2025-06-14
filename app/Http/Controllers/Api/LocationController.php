<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class LocationController extends Controller
{
    /**
     * Display a listing of locations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Location::query();
        
        // Filter by city if specified
        if ($request->has('city')) {
            $query->where('city', $request->city);
        }
        
        // Filter by region if specified
        if ($request->has('region')) {
            $query->where('region', $request->region);
        }
        
        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
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
            $query->orderBy('city')
                  ->orderBy('name');
        }
        
        return $query->paginate($request->input('per_page', 20));
    }
    
    /**
     * Store a newly created location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', Location::class);
        
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'nullable|string|max:255',
                'address_two' => 'nullable|string|max:255',
                'city' => 'required|string|max:100',
                'region' => 'required|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'required|string|max:100',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'google_place_id' => 'nullable|string|max:255',
                'is_verified' => 'boolean',
            ]);
            
            $location = Location::create($validated);
            
            // Clear locations cache if using caching
            Cache::forget('popular_locations');
            
            return response()->json([
                'message' => 'Location created successfully',
                'data' => $location
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        }
    }
    
    /**
     * Display the specified location.
     *
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function show(Location $location)
    {
        $this->authorize('view', $location);
        
        return response()->json([
            'data' => $location
        ]);
    }
    
    /**
     * Display spotlights at the specified location.
     *
     * @param  \App\Models\Location  $location
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function spotlights(Location $location, Request $request)
    {
        $this->authorize('view', $location);
        
        $spotlights = $location->spotlights()
            ->with(['category', 'tags'])
            ->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));
        
        return response()->json([
            'data' => $spotlights
        ]);
    }
    
    /**
     * Update the specified location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Location $location)
    {
        $this->authorize('update', $location);
        
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'address' => 'nullable|string|max:255',
                'address_two' => 'nullable|string|max:255',
                'city' => 'sometimes|string|max:100',
                'region' => 'sometimes|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'sometimes|string|max:100',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'google_place_id' => 'nullable|string|max:255',
                'is_verified' => 'boolean',
            ]);
            
            $location->update($validated);
            
            // Clear locations cache if using caching
            Cache::forget('popular_locations');
            
            return response()->json([
                'message' => 'Location updated successfully',
                'data' => $location
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        }
    }
    
    /**
     * Remove the specified location from storage.
     *
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function destroy(Location $location)
    {
        $this->authorize('delete', $location);
        
        // Check if location is used in spotlights
        if ($location->spotlights()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete location that is in use',
            ], 422);
        }
        
        $location->delete();
        
        // Clear locations cache if using caching
        Cache::forget('popular_locations');
        
        return response()->json([
            'message' => 'Location deleted successfully'
        ]);
    }
}
