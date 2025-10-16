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
        // Validate request parameters
        $request->validate([
            'per_page' => 'integer|min:1|max:50',
            'category_id' => 'integer|exists:event_categories,id',
            'location_id' => 'integer|exists:event_locations,id',
            'featured' => 'boolean',
            'month' => 'integer|min:1|max:12',
            'day' => 'integer|min:1|max:31',
            'year' => 'integer|min:2020|max:2050',
        ]);

        $perPage = min($request->get('per_page', 15), 50);
        
        $query = Event::with(['eventCategory', 'eventLocation', 'schedules'])
            ->published()
            ->upcoming()
            ->orderBy('created_at', 'desc');

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('event_category_id', $request->category_id);
        }

        // Filter by location
        if ($request->has('location_id')) {
            $query->where('event_location_id', $request->location_id);
        }

        // Filter by featured
        if ($request->has('featured') && $request->featured) {
            $query->featured();
        }

        // Past events are excluded by default using scopeUpcoming()

        // Filter by date (month, day, year)
        if ($request->has('month') || $request->has('day') || $request->has('year')) {
            $query->whereHas('schedules', function ($q) use ($request) {
                // Default to current year if not provided
                $year = $request->get('year', now()->year);
                
                if ($request->has('month')) {
                    $month = str_pad($request->month, 2, '0', STR_PAD_LEFT);
                    
                    if ($request->has('day')) {
                        // Filter by specific date (month, day, year)
                        $day = str_pad($request->day, 2, '0', STR_PAD_LEFT);
                        $date = "{$year}-{$month}-{$day}";
                        $q->whereDate('date', $date);
                    } else {
                        // Filter by month and year only
                        $q->whereYear('date', $year)
                          ->whereMonth('date', $request->month);
                    }
                } else if ($request->has('year')) {
                    // Filter by year only
                    $q->whereYear('date', $year);
                }
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
            ->upcoming()
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
            ->upcoming()
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
            ->upcoming()
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
