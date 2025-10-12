<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'kids_price' => $this->kids_price,
            'infant_price' => $this->infant_price,
            'capacity' => $this->capacity,
            'display_order' => $this->display_order,
            'active' => $this->active,
            'location' => new TourLocationResource($this->whenLoaded('location')),
            'images' => TourImageResource::collection($this->whenLoaded('images')),
            'available_days' => $this->whenLoaded('dayAvailabilities', function() {
                return $this->dayAvailabilities->pluck('day');
            }),
            'date_ranges' => TourDateRangeResource::collection($this->whenLoaded('dateRanges')),
            'average_rating' => $this->average_rating,
            'review_count' => $this->review_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
