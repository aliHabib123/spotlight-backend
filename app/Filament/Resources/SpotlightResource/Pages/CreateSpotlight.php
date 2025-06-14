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
}
