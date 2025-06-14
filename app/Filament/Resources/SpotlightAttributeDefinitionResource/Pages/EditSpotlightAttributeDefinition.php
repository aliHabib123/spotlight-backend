<?php

namespace App\Filament\Resources\SpotlightAttributeDefinitionResource\Pages;

use App\Filament\Resources\SpotlightAttributeDefinitionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSpotlightAttributeDefinition extends EditRecord
{
    protected static string $resource = SpotlightAttributeDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
