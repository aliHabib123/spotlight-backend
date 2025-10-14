<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TourResource;
use App\Http\Resources\TourRatingResource;
use App\Models\Tour;
use App\Models\TourRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Carbon;

class TourController extends Controller
{
    /**
     * Display a listing of tours.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = Tour::query()->with(['location', 'images', 'dayAvailabilities', 'dateRanges'])->where('active', true);
        
        // Filter by location
        if ($request->has('location_id')) {
            $query->where('tour_location_id', $request->location_id);
        }
        
        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        
        // Filter by featured status
        if ($request->filled('featured')) {
            $val = $request->featured;
            $isFeatured = in_array($val, [1, '1', true, 'true'], true);
            $query->where('is_featured', $isFeatured);
        }
        if ($request->filled('is_featured')) {
            $val = $request->is_featured;
            $isFeatured = in_array($val, [1, '1', true, 'true'], true);
            $query->where('is_featured', $isFeatured);
        }
        
        // Filter by specific date (YYYY-MM-DD)
        if ($request->filled('date')) {
            try {
                $date = Carbon::parse($request->date)->format('Y-m-d');
                $dow = strtolower(Carbon::parse($request->date)->format('l'));
                // Must be available on that day of week
                $query->whereHas('dayAvailabilities', function (Builder $q) use ($dow) {
                    $q->where('day', $dow);
                });
                // And within any defined date range (or no ranges defined)
                $query->where(function (Builder $q) use ($date) {
                    $q->whereDoesntHave('dateRanges')
                      ->orWhereHas('dateRanges', function (Builder $qr) use ($date) {
                          $qr->where('start_date', '<=', $date)
                             ->where('end_date', '>=', $date);
                      });
                });
            } catch (\Exception $e) {
                // Ignore invalid date format; no filter applied
            }
        }
        
        // Sort options
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'price_low_high':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_high_low':
                    $query->orderBy('price', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'rating':
                    $query->orderByDesc(function (Builder $query) {
                        $query->select('AVG(rating)')
                              ->from('tour_ratings')
                              ->whereColumn('tour_id', 'tours.id');
                    });
                    break;
                default:
                    $query->orderBy('display_order', 'asc');
                    break;
            }
        } else {
            $query->orderBy('display_order', 'asc');
        }
        
        // Pagination
        $perPage = $request->per_page ?? 15;
        $tours = $query->paginate($perPage);
        
        return TourResource::collection($tours);
    }
    
    /**
     * Store a newly created tour in storage.
     * This endpoint is available only for tour admin, admin and super admin roles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\TourResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Check permission
        if (!Auth::check() || !Auth::user()->hasPermissionTo('create tours')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'kids_price' => 'nullable|numeric|min:0',
            'infant_price' => 'nullable|numeric|min:0',
            'capacity' => 'nullable|integer|min:1',
            'tour_location_id' => 'required|exists:tour_locations,id',
            'available_days' => 'required|array|min:1',
            'available_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'date_ranges' => 'nullable|array',
            'date_ranges.*.start_date' => 'required_with:date_ranges|date',
            'date_ranges.*.end_date' => 'required_with:date_ranges|date',
        ]);

        // Additional validation: ensure each date range has end_date >= start_date
        $validator->after(function ($validator) use ($request) {
            if (is_array($request->date_ranges ?? null)) {
                foreach ($request->date_ranges as $idx => $range) {
                    $start = $range['start_date'] ?? null;
                    $end = $range['end_date'] ?? null;
                    if ($start && $end && strtotime($end) < strtotime($start)) {
                        $validator->errors()->add("date_ranges.$idx.end_date", 'The end date must be a date after or equal to the start date.');
                    }
                }
            }
        });
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Create the tour
        $tour = new Tour($request->except('available_days'));
        $tour->user_id = Auth::id();
        
        // Tours created by regular tour admins need approval
        if (!Auth::check() || !(Auth::user()->hasRole('super admin') || Auth::user()->hasRole('admin'))) {
            $tour->active = false;
        }
        
        $tour->save();
        
        // Add date ranges if provided
        if (is_array($request->date_ranges ?? null)) {
            foreach ($request->date_ranges as $range) {
                if (!empty($range['start_date']) && !empty($range['end_date'])) {
                    $tour->dateRanges()->create([
                        'start_date' => $range['start_date'],
                        'end_date' => $range['end_date'],
                    ]);
                }
            }
        }
        
        // Add available days
        foreach ($request->available_days as $day) {
            $tour->dayAvailabilities()->create(['day' => $day]);
        }
        
        return new TourResource($tour->load(['location', 'dayAvailabilities', 'dateRanges']));
    }
    
    /**
     * Display the specified tour.
     *
     * @param  int  $id
     * @return \App\Http\Resources\TourResource
     */
    public function show($id)
    {
        $tour = Tour::with(['location', 'images', 'dayAvailabilities', 'dateRanges'])
                   ->where('active', true)
                   ->findOrFail($id);
        
        // If user is logged in, check if they have a rating
        if (Auth::check()) {
            $tour->load(['ratings' => function($query) {
                $query->where('user_id', Auth::id());
            }]);
            
            $tour->user_rating = $tour->ratings->first() ? 
                new TourRatingResource($tour->ratings->first()) : 
                null;
        }
        
        return new TourResource($tour);
    }
    
    /**
     * Update the specified tour in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \App\Http\Resources\TourResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $tour = Tour::findOrFail($id);
        
        // Check permissions
        if (!Auth::check() || !Auth::user()->hasPermissionTo('edit tours') || 
            (!(Auth::user()->hasRole('super admin') || Auth::user()->hasRole('admin')) && $tour->user_id !== Auth::id())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'kids_price' => 'nullable|numeric|min:0',
            'infant_price' => 'nullable|numeric|min:0',
            'capacity' => 'nullable|integer|min:1',
            'tour_location_id' => 'sometimes|required|exists:tour_locations,id',
            'available_days' => 'sometimes|required|array|min:1',
            'available_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'date_ranges' => 'nullable|array',
            'date_ranges.*.start_date' => 'required_with:date_ranges|date',
            'date_ranges.*.end_date' => 'required_with:date_ranges|date',
        ]);

        // Additional validation: ensure each date range has end_date >= start_date
        $validator->after(function ($validator) use ($request) {
            if (is_array($request->date_ranges ?? null)) {
                foreach ($request->date_ranges as $idx => $range) {
                    $start = $range['start_date'] ?? null;
                    $end = $range['end_date'] ?? null;
                    if ($start && $end && strtotime($end) < strtotime($start)) {
                        $validator->errors()->add("date_ranges.$idx.end_date", 'The end date must be a date after or equal to the start date.');
                    }
                }
            }
        });
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // If tour admin is updating, don't allow them to change active status
        if (!Auth::check() || !(Auth::user()->hasRole('super admin') || Auth::user()->hasRole('admin'))) {
            $tour->fill($request->except(['available_days', 'active']));
        } else {
            $tour->fill($request->except('available_days'));
        }
        
        $tour->save();
        
        // Update available days if provided
        if ($request->has('available_days')) {
            $tour->dayAvailabilities()->delete();
            foreach ($request->available_days as $day) {
                $tour->dayAvailabilities()->create(['day' => $day]);
            }
        }
        
        // Update date ranges if provided
        if ($request->has('date_ranges')) {
            $tour->dateRanges()->delete();
            if (is_array($request->date_ranges)) {
                foreach ($request->date_ranges as $range) {
                    if (!empty($range['start_date']) && !empty($range['end_date'])) {
                        $tour->dateRanges()->create([
                            'start_date' => $range['start_date'],
                            'end_date' => $range['end_date'],
                        ]);
                    }
                }
            }
        }
        
        return new TourResource($tour->load(['location', 'dayAvailabilities', 'dateRanges']));
    }
    
    /**
     * Remove the specified tour from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $tour = Tour::findOrFail($id);
        
        // Check permissions
        if (!Auth::check() || !Auth::user()->hasPermissionTo('delete tours') || 
            (!(Auth::user()->hasRole('super admin') || Auth::user()->hasRole('admin')) && $tour->user_id !== Auth::id())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Delete related resources
        $tour->dayAvailabilities()->delete();
        $tour->ratings()->delete();
        
        // Delete the tour itself
        $tour->delete();
        
        return response()->json(['message' => 'Tour deleted successfully']);
    }
    
    /**
     * Approve a tour.
     *
     * @param  int  $id
     * @return \App\Http\Resources\TourResource|\Illuminate\Http\JsonResponse
     */
    public function approve($id)
    {
        $tour = Tour::findOrFail($id);
        
        // Check permissions - only admins can approve tours
        if (!Auth::check() || !Auth::user()->hasPermissionTo('approve tours')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $tour->active = true;
        $tour->save();
        
        return new TourResource($tour);
    }
    
    /**
     * Rate a tour.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \App\Http\Resources\TourRatingResource|\Illuminate\Http\JsonResponse
     */
    public function rate(Request $request, $id)
    {
        // Validate tour exists and is active
        $tour = Tour::where('active', true)->findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Check if user already rated this tour
        $existingRating = TourRating::where('tour_id', $id)
                                   ->where('user_id', Auth::id())
                                   ->first();
        
        if ($existingRating) {
            $existingRating->rating = $request->rating;
            $existingRating->comment = $request->comment;
            $existingRating->save();
            $rating = $existingRating;
        } else {
            $rating = new TourRating([
                'tour_id' => $id,
                'user_id' => Auth::id(),
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);
            $rating->save();
        }
        
        return new TourRatingResource($rating->load('user'));
    }
    
    /**
     * Check if the authenticated user has rated a tour.
     *
     * @param  int  $id
     * @return \App\Http\Resources\TourRatingResource|\Illuminate\Http\JsonResponse
     */
    public function checkRating($id)
    {
        // Validate tour exists
        $tour = Tour::findOrFail($id);
        
        $rating = TourRating::where('tour_id', $id)
                           ->where('user_id', Auth::id())
                           ->first();
        
        if ($rating) {
            return new TourRatingResource($rating);
        } else {
            return response()->json(['message' => 'No rating found'], 404);
        }
    }
    
    /**
     * Get ratings for a tour.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getRatings(Request $request, $id)
    {
        // Validate tour exists and is active
        $tour = Tour::where('active', true)->findOrFail($id);
        
        $perPage = $request->per_page ?? 15;
        
        $ratings = TourRating::with('user')
                            ->where('tour_id', $id)
                            ->orderBy('created_at', 'desc')
                            ->paginate($perPage);
        
        return TourRatingResource::collection($ratings);
    }
}
