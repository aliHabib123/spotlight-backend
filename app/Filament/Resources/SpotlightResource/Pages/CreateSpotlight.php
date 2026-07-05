<?php

namespace App\Filament\Resources\SpotlightResource\Pages;

use App\Filament\Resources\SpotlightResource;
use App\Models\SpotlightAttributeDefinition;
use App\Models\SpotlightAttributeValue;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSpotlight extends CreateRecord
{
    protected static string $resource = SpotlightResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store send_notification preference in session for observer
        // Always set the session value - if checkbox is unchecked, it won't be in $data
        $sendNotification = array_key_exists('send_notification', $data)
            ? (bool) $data['send_notification']
            : true; // align with UI default
        session(['spotlight_send_notification' => $sendNotification]);
        
        // Log for debugging
        \Illuminate\Support\Facades\Log::debug('Spotlight create - notification setting', [
            'send_notification_in_form' => array_key_exists('send_notification', $data) ? $data['send_notification'] : 'not_set',
            'final_notification_setting' => $sendNotification
        ]);
        
        // Remove from data since it's not a database field
        if (isset($data['send_notification'])) {
            unset($data['send_notification']);
        }
        
        // Handle video file upload
        if (isset($data['video_provider']) && $data['video_provider'] === 'self' && isset($data['video_file'])) {
            // Get the file path from Filament's temporary upload
            $filePath = $data['video_file'];
            
            // Set the video URL to the storage path
            $data['video_url'] = asset('storage/' . $filePath);
            
            // Log for debugging
            \Illuminate\Support\Facades\Log::debug('Video file upload in Filament Create', [
                'video_file' => $filePath,
                'video_url' => $data['video_url']
            ]);
        }
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        $this->syncPrimaryLocation();
        $this->saveAttributeValues();
    }

    /**
     * Keep the legacy single location_id column pointing at the first selected
     * location so older app versions still receive a `location` object.
     */
    protected function syncPrimaryLocation(): void
    {
        $formData = $this->form->getState();
        $locationIds = $formData['locations'] ?? [];
        $primary = is_array($locationIds) && ! empty($locationIds) ? (int) $locationIds[0] : null;

        if ($this->record->location_id !== $primary) {
            $this->record->location_id = $primary;
            $this->record->saveQuietly();
        }
    }
    
    protected function saveAttributeValues(): void
    {
        $formData = $this->form->getState();
        
        if (isset($formData['attributes']) && is_array($formData['attributes'])) {
            foreach ($formData['attributes'] as $definitionId => $value) {
                $definition = SpotlightAttributeDefinition::find($definitionId);
                
                if (!$definition) continue;
                
                // Skip null values
                if ($value === null) continue;
                
                if ($definition->isEnum()) {
                    // Handle enum types - work with both single and multi-select scenarios
                    $optionIds = is_array($value) ? $value : [$value];
                    
                    // Make sure we're only working with non-empty values
                    $optionIds = array_filter($optionIds, function($id) {
                        return !empty($id) && $id !== null;
                    });
                    
                    // Create values for each option ID
                    foreach ($optionIds as $optionId) {
                        SpotlightAttributeValue::create([
                            'spotlight_id' => $this->record->id,
                            'attribute_definition_id' => $definitionId,
                            'attribute_option_id' => $optionId,
                        ]);
                    }
                } else {
                    // Handle non-enum types
                    SpotlightAttributeValue::create([
                        'spotlight_id' => $this->record->id,
                        'attribute_definition_id' => $definitionId,
                        'value' => (string) $value,
                    ]);
                }
            }
        }
    }
}
