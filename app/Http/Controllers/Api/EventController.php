<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    /**
     * Display a listing of events.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 50);
        
        $query = Event::with(['eventCategory', 'eventLocation', 'schedules'])
            ->published()
            ->orderBy('created_at', 'desc');

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('event_category_id', $request->category_id);
        }

        // Filter by featured
        if ($request->has('featured') && $request->featured) {
            $query->featured();
        }

        // Filter by upcoming events only
        if ($request->has('upcoming') && $request->upcoming) {
            $query->whereHas('schedules', function ($q) {
                $q->where('date', '>=', now()->toDateString());
            });
        }

        $events = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $events->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
            ]
        ]);
    }

    /**
     * Display the specified event.
     */
    public function show(string $id): JsonResponse
    {
        $event = Event::with(['eventCategory', 'eventLocation', 'schedules'])
            ->published()
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $event
        ]);
    }

    /**
     * Get featured events.
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 5), 20);

        $events = Event::with(['eventCategory', 'eventLocation', 'schedules'])
            ->published()
            ->featured()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $events
        ]);
    }

    /**
     * Get upcoming events.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 10), 50);

        $events = Event::with(['eventCategory', 'eventLocation', 'schedules'])
            ->published()
            ->whereHas('schedules', function ($q) {
                $q->where('date', '>=', now()->toDateString());
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $events
        ]);
    }

    /**
     * Get events by category.
     */
    public function byCategory(string $categoryId, Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 50);

        // Verify category exists
        $category = EventCategory::findOrFail($categoryId);

        $events = Event::with(['eventCategory', 'eventLocation', 'schedules'])
            ->published()
            ->where('event_category_id', $categoryId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $events->items(),
            'category' => $category,
            'meta' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
            ]
        ]);
    }
}
