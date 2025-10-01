<?php

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use App\Models\TourDayAvailability;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateTour extends CreateRecord
{
    protected static string $resource = TourResource::class;
    
    /**
     * @param array $data
     * @return Model
     */
    protected function handleRecordCreation(array $data): Model
    {   
        // Get available days and remove from data array
        $availableDaysData = $data['available_days'] ?? [];
        unset($data['available_days']);
        
        // Validate at least one day is selected
        $hasSelectedDay = false;
        foreach ($availableDaysData as $value) {
            if ($value) {
                $hasSelectedDay = true;
                break;
            }
        }
        
        if (!$hasSelectedDay) {
            throw ValidationException::withMessages([
                'available_days' => 'Please select at least one available day.',
            ]);
        }
        
        // Create the tour record
        $tour = static::getModel()::create($data);
        
        // Create day availabilities
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($days as $day) {
            if (isset($availableDaysData[$day]) && $availableDaysData[$day]) {
                TourDayAvailability::create([
                    'tour_id' => $tour->id,
                    'day' => $day,
                ]);
            }
        }
        
        return $tour;
    }
}
