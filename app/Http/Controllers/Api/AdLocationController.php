<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AdLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $adLocations = AdLocation::withCount('ads')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $adLocations
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!Gate::allows('manage ad locations')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);
        
        $adLocation = AdLocation::create($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Ad location created successfully',
            'data' => $adLocation
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $adLocation = AdLocation::with('activeAds')->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $adLocation
        ]);
    }
    
    /**
     * Display the specified resource by slug.
     */
    public function showBySlug(string $slug)
    {
        $adLocation = AdLocation::where('slug', $slug)->with('activeAds')->firstOrFail();
        
        return response()->json([
            'status' => 'success',
            'data' => $adLocation
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!Gate::allows('manage ad locations')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $adLocation = AdLocation::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);
        
        $adLocation->update($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Ad location updated successfully',
            'data' => $adLocation
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!Gate::allows('manage ad locations')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $adLocation = AdLocation::findOrFail($id);
        
        // Check if there are ads associated with this location
        if ($adLocation->ads()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete ad location with associated ads'
            ], 422);
        }
        
        $adLocation->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Ad location deleted successfully'
        ]);
    }
}
