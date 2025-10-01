<?php

namespace App\Observers;

use App\Models\Tour;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TourObserver
{
    /**
     * Handle the Tour "saving" event.
     */
    public function saving(Tour $tour): void
    {
        // Set user_id if not set
        if (empty($tour->user_id) && Auth::check()) {
            $tour->user_id = Auth::id();
        }
        
        // Generate slug if empty
        if (empty($tour->slug) && !empty($tour->title)) {
            $tour->slug = $this->generateUniqueSlug($tour);
        }
    }
    
    /**
     * Generate a unique slug for the tour.
     */
    private function generateUniqueSlug(Tour $tour): string
    {
        $slug = Str::slug($tour->title);
        $originalSlug = $slug;
        $count = 1;
        
        // Check if the slug exists
        while (Tour::where('slug', $slug)
            ->where('id', '!=', $tour->id ?? 0)
            ->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }
        
        return $slug;
    }

    /**
     * Handle the Tour "created" event.
     */
    public function created(Tour $tour): void
    {
        //
    }

    /**
     * Handle the Tour "updated" event.
     */
    public function updated(Tour $tour): void
    {
        //
    }

    /**
     * Handle the Tour "deleted" event.
     */
    public function deleted(Tour $tour): void
    {
        //
    }

    /**
     * Handle the Tour "restored" event.
     */
    public function restored(Tour $tour): void
    {
        //
    }

    /**
     * Handle the Tour "force deleted" event.
     */
    public function forceDeleted(Tour $tour): void
    {
        //
    }
}
