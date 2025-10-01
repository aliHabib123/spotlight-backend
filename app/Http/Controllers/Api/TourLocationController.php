<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TourLocationResource;
use App\Http\Resources\TourResource;
use App\Models\TourLocation;
use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TourLocationController extends Controller
{
    /**
     * Display a listing of tour locations.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $locations = TourLocation::withCount('tours')
                              ->orderBy('name')
                              ->get();

        return TourLocationResource::collection($locations);
    }

    /**
     * Display the specified tour location.
     *
     * @param  int  $id
     * @return \App\Http\Resources\TourLocationResource
     */
    public function show($id)
    {
        $location = TourLocation::withCount('tours')->findOrFail($id);

        return new TourLocationResource($location);
    }

    /**
     * Get tours for a specific location.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function tours(Request $request, $id)
    {
        // Verify location exists
        $location = TourLocation::findOrFail($id);

        $query = Tour::with(['location', 'images'])
                    ->where('tour_location_id', $id)
                    ->where('active', true);

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
                default:
                    $query->orderBy('display_order', 'asc');
                    break;
            }
        } else {
            $query->orderBy('display_order', 'asc');
        }
        
        $perPage = $request->per_page ?? 15;
        $tours = $query->paginate($perPage);

        return TourResource::collection($tours);
    }
}
