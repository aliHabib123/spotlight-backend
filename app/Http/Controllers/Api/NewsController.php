<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = News::with(['category', 'author'])
            ->published()
            ->orderBy('published_at', 'desc');
            
        // Filter by category if provided
        if ($request->has('category_id')) {
            $query->where('news_category_id', $request->category_id);
        }
        
        // Filter by category slug if provided
        if ($request->has('category_slug')) {
            $category = NewsCategory::where('slug', $request->category_slug)->first();
            if ($category) {
                $query->where('news_category_id', $category->id);
            }
        }
        
        // Search by title or content if provided
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%");
            });
        }
        
        // Paginate results
        $perPage = $request->get('per_page', 10);
        $news = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $news
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Check if user has permission to create news
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:500',
            'content' => 'required|string',
            'news_category_id' => 'required|exists:news_categories,id',
            'featured_image' => 'nullable|image|max:2048', // 2MB max
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'show_date' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Handle featured image upload
        $featuredImagePath = null;
        if ($request->hasFile('featured_image')) {
            $featuredImagePath = $request->file('featured_image')->store('news', 'public');
        }
        
        // Create news article
        $news = News::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'summary' => $request->summary,
            'content' => $request->input('content'),
            'news_category_id' => $request->news_category_id,
            'user_id' => auth('api')->id(),
            'featured_image' => $featuredImagePath,
            'is_published' => $request->is_published ?? false,
            'published_at' => $request->is_published ? ($request->published_at ?? now()) : null,
            'show_date' => $request->has('show_date') ? $request->show_date : true,
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'News article created successfully',
            'data' => $news->load(['category', 'author'])
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        // Use id or slug lookup on separate indexed columns instead of OR (avoids index merge)
        $query = News::with([
            'category:id,name,slug',
            'author:id,name',
        ]);

        $news = is_numeric($id)
            ? $query->find((int) $id)
            : $query->where('slug', $id)->first();

        if (!$news) {
            return response()->json([
                'status' => 'error',
                'message' => 'News article not found'
            ], 404);
        }

        // If news is not published, check if user has permission to view it
        if (!$news->is_published && auth('api')->check()) {
            $user = auth('api')->user();
            if ($news->user_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 403);
            }
        } elseif (!$news->is_published) {
            return response()->json([
                'status' => 'error',
                'message' => 'News article not found'
            ], 404);
        }

        // Get related news from the same category
        $relatedNews = News::where('news_category_id', $news->news_category_id)
            ->where('id', '!=', $news->id)
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get(['id', 'title', 'slug', 'summary', 'featured_image', 'published_at']);

        $response = response()->json([
            'status' => 'success',
            'data' => [
                'news' => $news,
                'related_news' => $relatedNews
            ]
        ]);

        // Articles change infrequently — allow clients/CDN to cache for 5 minutes
        $response->headers->set('Cache-Control', 'public, max-age=300, stale-while-revalidate=60');

        return $response;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $news = News::find($id);
        
        if (!$news) {
            return response()->json([
                'status' => 'error',
                'message' => 'News article not found'
            ], 404);
        }
        
        // Check if user has permission to update news
        $user = auth('api')->user();
        if (!$user || $news->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'summary' => 'nullable|string|max:500',
            'content' => 'sometimes|required|string',
            'news_category_id' => 'sometimes|required|exists:news_categories,id',
            'featured_image' => 'nullable|image|max:2048', // 2MB max
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'show_date' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            // Delete old image if exists
            if ($news->featured_image) {
                Storage::disk('public')->delete($news->featured_image);
            }
            
            $featuredImagePath = $request->file('featured_image')->store('news', 'public');
            $news->featured_image = $featuredImagePath;
        }
        
        // Update news article fields
        if ($request->has('title')) {
            $news->title = $request->title;
            $news->slug = Str::slug($request->title);
        }
        
        if ($request->has('summary')) {
            $news->summary = $request->summary;
        }
        
        if ($request->has('content')) {
            $news->content = $request->input('content');
        }
        
        if ($request->has('news_category_id')) {
            $news->news_category_id = $request->news_category_id;
        }
        
        if ($request->has('is_published')) {
            $news->is_published = $request->is_published;
            
            // Set published_at if publishing for the first time
            if ($request->is_published && !$news->published_at) {
                $news->published_at = $request->published_at ?? now();
            }
        }
        
        if ($request->has('published_at') && $news->is_published) {
            $news->published_at = $request->published_at;
        }
        
        if ($request->has('show_date')) {
            $news->show_date = $request->show_date;
        }
        
        $news->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'News article updated successfully',
            'data' => $news->load(['category', 'author'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $news = News::find($id);
        
        if (!$news) {
            return response()->json([
                'status' => 'error',
                'message' => 'News article not found'
            ], 404);
        }
        
        // Check if user has permission to delete news
        $user = auth('api')->user();
        if (!$user || $news->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Delete featured image if exists
        if ($news->featured_image) {
            Storage::disk('public')->delete($news->featured_image);
        }
        
        $news->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'News article deleted successfully'
        ]);
    }

    /**
     * Display the latest news articles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function latest(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 5);
        
        $latestNews = News::with(['category', 'author'])
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
            
        return response()->json([
            'status' => 'success',
            'data' => $latestNews
        ]);
    }

    /**
     * Display featured news articles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 5);
        
        $featuredNews = News::with(['category', 'author'])
            ->published()
            ->where('is_featured', true)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
            
        return response()->json([
            'status' => 'success',
            'data' => $featuredNews
        ]);
    }
}
