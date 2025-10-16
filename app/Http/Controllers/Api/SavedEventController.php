<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedEvent;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedEventController extends Controller
{
    /**
     * Get all events saved by the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - User not authenticated',
            ], 401);
        }

        $perPage = $request->input('per_page', 10);

        $eventIds = SavedEvent::where('user_id', $user->id)
            ->pluck('event_id');

        $savedEvents = Event::whereIn('id', $eventIds)
            ->with(['eventCategory', 'eventLocation', 'schedules'])
            ->published()
            ->upcoming()
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $savedEvents,
        ]);
    }

    /**
     * Save an event for the authenticated user.
     */
    public function save($id): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - User not authenticated',
            ], 401);
        }

        $event = Event::findOrFail($id);

        $existing = SavedEvent::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'success',
                'message' => 'Event is already saved',
                'data' => $existing,
            ]);
        }

        $savedEvent = new SavedEvent([
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);
        $savedEvent->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Event saved successfully',
            'data' => $savedEvent,
        ], 201);
    }

    /**
     * Unsave an event for the authenticated user.
     */
    public function unsave($id): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - User not authenticated',
            ], 401);
        }

        $saved = SavedEvent::where('user_id', $user->id)
            ->where('event_id', $id)
            ->first();

        if (!$saved) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event is not saved by this user',
            ], 404);
        }

        $saved->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Event unsaved successfully',
        ]);
    }

    /**
     * Check if an event is saved by the authenticated user.
     */
    public function check($id): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - User not authenticated',
            ], 401);
        }

        $isSaved = SavedEvent::where('user_id', $user->id)
            ->where('event_id', $id)
            ->exists();

        return response()->json([
            'status' => 'success',
            'data' => [
                'is_saved' => $isSaved,
            ],
        ]);
    }
}
