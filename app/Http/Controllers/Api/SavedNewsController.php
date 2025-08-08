<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedNews;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedNewsController extends Controller
{
    /**
     * Get all news saved by the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();
        
        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - User not authenticated',
            ], 401);
        }
        
        $perPage = $request->input('per_page', 10);
        
        // Get the IDs of saved news
        $newsIds = SavedNews::where('user_id', $user->id)
            ->pluck('news_id');
            
        // Get the actual news entities with their relationships
        $savedNews = News::whereIn('id', $newsIds)
            ->with(['category', 'author'])
            ->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $savedNews
        ]);
    }
    
    /**
     * Save a news item for the authenticated user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function save($id): JsonResponse
    {
        $user = Auth::guard('api')->user();
        
        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - User not authenticated'
            ], 401);
        }
        
        $news = News::findOrFail($id);
        
        // Check if already saved
        $existingSave = SavedNews::where('user_id', $user->id)
            ->where('news_id', $news->id)
            ->first();
            
        if ($existingSave) {
            return response()->json([
                'status' => 'success',
                'message' => 'News item is already saved',
                'data' => $existingSave
            ]);
        }
        
        // Save the news
        $savedNews = new SavedNews([
            'user_id' => $user->id,
            'news_id' => $news->id
        ]);
        
        $savedNews->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'News item saved successfully',
            'data' => $savedNews
        ], 201);
    }
    
    /**
     * Unsave a news item for the authenticated user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function unsave($id): JsonResponse
    {
        $user = Auth::guard('api')->user();
        
        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - User not authenticated'
            ], 401);
        }
        
        $savedNews = SavedNews::where('user_id', $user->id)
            ->where('news_id', $id)
            ->first();
            
        if (!$savedNews) {
            return response()->json([
                'status' => 'error',
                'message' => 'News item is not saved by this user'
            ], 404);
        }
        
        $savedNews->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'News item unsaved successfully'
        ]);
    }
    
    /**
     * Check if a news item is saved by the authenticated user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function check($id): JsonResponse
    {
        $user = Auth::guard('api')->user();
        
        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - User not authenticated'
            ], 401);
        }
        
        $isSaved = SavedNews::where('user_id', $user->id)
            ->where('news_id', $id)
            ->exists();
            
        return response()->json([
            'status' => 'success',
            'data' => [
                'is_saved' => $isSaved
            ]
        ]);
    }
}
