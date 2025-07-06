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
        $this->saveAttributeValues();
    }
    
    protected function saveAttributeValues(): void
    {
        $formData = $this->form->getState();
        
        if (isset($formData['attributes']) && is_array($formData['attributes'])) {
            foreach ($formData['attributes'] as $definitionId => $value) {
                $definition = SpotlightAttributeDefinition::find($definitionId);
                
                if (!$definition) continue;
                
                // Save new value
                if ($value !== null) {
                    if ($definition->type === 'enum') {
                        if (is_array($value)) {
                            // Handle multiple values for enum
                            foreach ($value as $optionId) {
                                SpotlightAttributeValue::create([
                                    'spotlight_id' => $this->record->id,
                                    'attribute_definition_id' => $definitionId,
                                    'attribute_option_id' => $optionId,
                                ]);
                            }
                        } else {
                            SpotlightAttributeValue::create([
                                'spotlight_id' => $this->record->id,
                                'attribute_definition_id' => $definitionId,
                                'attribute_option_id' => $value,
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
}
