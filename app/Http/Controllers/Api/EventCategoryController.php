<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EventCategoryController extends Controller
{
    /**
     * Display a listing of event categories.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 50);
        
        $query = EventCategory::where('is_active', true)
            ->withCount(['events' => function ($q) {
                $q->where('is_published', true);
            }])
            ->orderBy('name');

        $categories = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $categories->items(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
            ]
        ]);
    }

    /**
     * Display the specified event category.
     */
    public function show(string $id): JsonResponse
    {
        $category = EventCategory::where('is_active', true)
            ->withCount(['events' => function ($q) {
                $q->where('is_published', true);
            }])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $category
        ]);
    }

    /**
     * Get all active event categories (simple list).
     */
    public function all(): JsonResponse
    {
        $categories = EventCategory::where('is_active', true)
            ->withCount(['events' => function ($q) {
                $q->where('is_published', true);
            }])
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }
}
