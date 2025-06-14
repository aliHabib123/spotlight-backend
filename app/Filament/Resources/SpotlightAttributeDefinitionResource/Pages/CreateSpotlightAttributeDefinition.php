<?php

namespace App\Filament\Resources\SpotlightAttributeDefinitionResource\Pages;

use App\Filament\Resources\SpotlightAttributeDefinitionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSpotlightAttributeDefinition extends CreateRecord
{
    protected static string $resource = SpotlightAttributeDefinitionResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
