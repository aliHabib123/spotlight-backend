<?php

namespace App\Filament\Resources\SpotlightResource\Pages;

use App\Filament\Resources\SpotlightResource;
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
}
