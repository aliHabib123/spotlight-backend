<?php

namespace App\Observers;

use App\Models\TourLocation;
use Illuminate\Support\Str;

class TourLocationObserver
{
    /**
     * Handle the TourLocation "created" event.
     */
    public function created(TourLocation $tourLocation): void
    {
        // Generate slug if not explicitly set
        if (empty($tourLocation->slug) && !empty($tourLocation->name)) {
            $tourLocation->slug = $this->generateUniqueSlug($tourLocation);
            $tourLocation->save();
        }
    }
    
    /**
     * Generate a unique slug for the tour location.
     */
    private function generateUniqueSlug(TourLocation $tourLocation): string
    {
        $slug = Str::slug($tourLocation->name);
        $originalSlug = $slug;
        $count = 1;
        
        // Check if the slug exists
        while (TourLocation::where('slug', $slug)
            ->where('id', '!=', $tourLocation->id ?? 0)
            ->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }
        
        return $slug;
    }

    /**
     * Handle the TourLocation "updated" event.
     */
    public function updated(TourLocation $tourLocation): void
    {
        // Slug is already set in the saving method
    }

    /**
     * Handle the TourLocation "deleted" event.
     */
    public function deleted(TourLocation $tourLocation): void
    {
        //
    }

    /**
     * Handle the TourLocation "restored" event.
     */
    public function restored(TourLocation $tourLocation): void
    {
        //
    }

    /**
     * Handle the TourLocation "force deleted" event.
     */
    public function forceDeleted(TourLocation $tourLocation): void
    {
        //
    }
}
