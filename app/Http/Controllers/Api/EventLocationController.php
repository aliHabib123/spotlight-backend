<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventLocation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EventLocationController extends Controller
{
    /**
     * Display a listing of event locations.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 50);
        
        $query = EventLocation::withCount(['events' => function ($q) {
                $q->published()->upcoming();
            }])
            ->orderBy('name');

        $locations = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $locations->items(),
            'meta' => [
                'current_page' => $locations->currentPage(),
                'per_page' => $locations->perPage(),
                'total' => $locations->total(),
                'last_page' => $locations->lastPage(),
            ]
        ]);
    }

    /**
     * Display the specified event location.
     */
    public function show(string $id): JsonResponse
    {
        $location = EventLocation::withCount(['events' => function ($q) {
                $q->published()->upcoming();
            }])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $location
        ]);
    }

    /**
     * Get all active event locations (simple list).
     */
    public function all(): JsonResponse
    {
        $locations = EventLocation::withCount(['events' => function ($q) {
                $q->published()->upcoming();
            }])
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $locations
        ]);
    }

    /**
     * Get events by location.
     */
    public function events(string $locationId, Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 50);

        // Verify location exists
        $location = EventLocation::findOrFail($locationId);

        $events = $location->events()
            ->with(['eventCategory', 'eventLocation', 'schedules'])
            ->where('is_published', true)
            ->upcoming()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $events->items(),
            'location' => $location,
            'meta' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
            ]
        ]);
    }
}
