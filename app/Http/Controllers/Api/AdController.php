<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ads = Ad::with(['adLocation', 'user'])->active()->orderBy('display_order')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $ads
        ]);
    }
    
    /**
     * Get ads for a specific location
     */
    public function getAdsByLocation($locationId)
    {
        $adLocation = AdLocation::findOrFail($locationId);
        $ads = $adLocation->activeAds()->with(['user'])->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $ads
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link_url' => 'nullable|url|max:255',
            'ad_location_id' => 'required|exists:ad_locations,id',
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('ads', 'public');
            $validated['image'] = $path;
        }
        
        // Set the authenticated user as the owner
        $validated['user_id'] = Auth::id();
        
        $ad = Ad::create($validated);
        
        // Load relationships
        $ad->load(['adLocation', 'user']);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Ad created successfully',
            'data' => $ad
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ad = Ad::with(['adLocation', 'user'])->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $ad
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $ad = Ad::findOrFail($id);
        
        // Check if user has permission to update this ad
        if (!Gate::allows('manage ads') && Auth::id() !== $ad->user_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link_url' => 'nullable|url|max:255',
            'ad_location_id' => 'required|exists:ad_locations,id',
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($ad->image) {
                Storage::disk('public')->delete($ad->image);
            }
            
            $path = $request->file('image')->store('ads', 'public');
            $validated['image'] = $path;
        }
        
        $ad->update($validated);
        
        // Load relationships
        $ad->load(['adLocation', 'user']);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Ad updated successfully',
            'data' => $ad
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ad = Ad::findOrFail($id);
        
        // Check if user has permission to delete this ad
        if (!Gate::allows('manage ads') && Auth::id() !== $ad->user_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Delete the image file if it exists
        if ($ad->image) {
            Storage::disk('public')->delete($ad->image);
        }
        
        $ad->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Ad deleted successfully'
        ]);
    }
}
