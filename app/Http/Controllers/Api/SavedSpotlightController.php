<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedSpotlight;
use App\Models\Spotlight;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedSpotlightController extends Controller
{

    
    /**
     * Get all spotlights saved by the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->input('per_page', 10);
        
        // Get the IDs of saved spotlights
        $spotlightIds = SavedSpotlight::where('user_id', $user->id)
            ->pluck('spotlight_id');
            
        // Get the actual spotlight entities with their relationships
        $savedSpotlights = Spotlight::whereIn('id', $spotlightIds)
            ->with(['category', 'location'])
            ->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $savedSpotlights
        ]);
    }
    
    /**
     * Save a spotlight for the authenticated user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function save($id): JsonResponse
    {
        $user = Auth::user();
        $spotlight = Spotlight::findOrFail($id);
        
        // Check if already saved
        $existingSave = SavedSpotlight::where('user_id', $user->id)
            ->where('spotlight_id', $spotlight->id)
            ->first();
            
        if ($existingSave) {
            return response()->json([
                'status' => 'success',
                'message' => 'Spotlight is already saved',
                'data' => $existingSave
            ]);
        }
        
        // Save the spotlight
        $savedSpotlight = new SavedSpotlight([
            'user_id' => $user->id,
            'spotlight_id' => $spotlight->id
        ]);
        
        $savedSpotlight->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Spotlight saved successfully',
            'data' => $savedSpotlight
        ], 201);
    }
    
    /**
     * Unsave a spotlight for the authenticated user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function unsave($id): JsonResponse
    {
        $user = Auth::user();
        
        $savedSpotlight = SavedSpotlight::where('user_id', $user->id)
            ->where('spotlight_id', $id)
            ->first();
            
        if (!$savedSpotlight) {
            return response()->json([
                'status' => 'error',
                'message' => 'Spotlight is not saved by this user'
            ], 404);
        }
        
        $savedSpotlight->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Spotlight unsaved successfully'
        ]);
    }
    
    /**
     * Check if a spotlight is saved by the authenticated user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function check($id): JsonResponse
    {
        $user = Auth::user();
        
        $isSaved = SavedSpotlight::where('user_id', $user->id)
            ->where('spotlight_id', $id)
            ->exists();
            
        return response()->json([
            'status' => 'success',
            'data' => [
                'is_saved' => $isSaved
            ]
        ]);
    }
}
