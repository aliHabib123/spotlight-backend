<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_number' => $this->booking_number,
            'tour' => new TourResource($this->whenLoaded('tour')),
            'tour_id' => $this->tour_id,
            'user_id' => $this->user_id,
            'selected_date' => $this->selected_date,
            'adults' => $this->adults,
            'kids' => $this->kids,
            'infants' => $this->infants,
            'total_price' => $this->total_price,
            'status' => $this->status,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'special_requests' => $this->special_requests,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
