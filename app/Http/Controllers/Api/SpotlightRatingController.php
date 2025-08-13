<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Spotlight;
use App\Models\SpotlightRating;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SpotlightRatingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    
    /**
     * Get all ratings for a spotlight.
     *
     * @param int $id Spotlight ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(int $id): JsonResponse
    {
        $spotlight = Spotlight::findOrFail($id);
        
        $ratings = $spotlight->ratings()
            ->with('user:id,name,username')
            ->latest()
            ->paginate(10);
        
        return response()->json([
            'status' => 'success',
            'data' => $ratings
        ]);
    }
    
    /**
     * Rate a spotlight.
     *
     * @param Request $request
     * @param int $id Spotlight ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function rate(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $spotlight = Spotlight::findOrFail($id);
        $user = Auth::user();
        
        // Check if user already rated this spotlight
        $existingRating = $spotlight->ratings()
            ->where('user_id', $user->id)
            ->first();
            
        if ($existingRating) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already rated this spotlight',
                'data' => $existingRating
            ], 422);
        }
        
        // Create new rating
        $rating = $spotlight->ratings()->create([
            'user_id' => $user->id,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);
        
        // Update average rating on the spotlight
        $spotlight->updateAverageRating();
        
        // Reload the spotlight to get updated average_rating and review_count
        $spotlight = $spotlight->fresh();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Spotlight rated successfully',
            'data' => $rating->load('user:id,name,username'),
            'average_rating' => $spotlight->average_rating,
            'review_count' => $spotlight->review_count
        ]);
    }
    
    /**
     * Update an existing rating.
     *
     * @param Request $request
     * @param int $id Spotlight ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $spotlight = Spotlight::findOrFail($id);
        $user = Auth::user();
        
        // Get existing rating
        $rating = $spotlight->ratings()
            ->where('user_id', $user->id)
            ->firstOrFail();
            
        // Update rating
        $rating->update([
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);
        
        // Update average rating on the spotlight
        $spotlight->updateAverageRating();
        
        // Reload the spotlight to get updated average_rating and review_count
        $spotlight = $spotlight->fresh();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Rating updated successfully',
            'data' => $rating->fresh()->load('user:id,name,username'),
            'average_rating' => $spotlight->average_rating,
            'review_count' => $spotlight->review_count
        ]);
    }
    
    /**
     * Delete a rating.
     *
     * @param int $id Spotlight ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        $spotlight = Spotlight::findOrFail($id);
        $user = Auth::user();
        
        // Get existing rating
        $rating = $spotlight->ratings()
            ->where('user_id', $user->id)
            ->first();
            
        if (!$rating) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rating not found'
            ], 404);
        }
        
        // Delete rating
        $rating->delete();
        
        // Update average rating on the spotlight
        $spotlight->updateAverageRating();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Rating deleted successfully'
        ]);
    }
    
    /**
     * Check if user has rated a spotlight.
     *
     * @param int $id Spotlight ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(int $id): JsonResponse
    {
        $spotlight = Spotlight::findOrFail($id);
        $user = Auth::user();
        
        $rating = $spotlight->ratings()
            ->where('user_id', $user->id)
            ->first();
        
        $averageRating = $spotlight->average_rating;
        $reviewCount = $spotlight->review_count;
        
        if ($rating) {
            return response()->json([
                'status' => 'success',
                'rated' => true,
                'data' => $rating,
                'average_rating' => $averageRating,
                'review_count' => $reviewCount
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'rated' => false,
            'average_rating' => $averageRating,
            'review_count' => $reviewCount
        ]);
    }
}
