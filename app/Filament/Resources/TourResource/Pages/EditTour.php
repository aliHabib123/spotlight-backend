<?php

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use App\Models\TourDayAvailability;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditTour extends EditRecord
{
    protected static string $resource = TourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    /**
     * @param Model $record
     * @param array $data
     * @return Model
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
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
        
        // Delete existing day availabilities
        $record->dayAvailabilities()->delete();
        
        // Create new day availabilities
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($days as $day) {
            if (isset($availableDaysData[$day]) && $availableDaysData[$day]) {
                TourDayAvailability::create([
                    'tour_id' => $record->id,
                    'day' => $day,
                ]);
            }
        }
        
        // Update tour record
        $record->update($data);
        
        return $record;
    }
}
