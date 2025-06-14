<?php

namespace App\Filament\Resources\SpotlightAttributeDefinitionResource\Pages;

use App\Filament\Resources\SpotlightAttributeDefinitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSpotlightAttributeDefinitions extends ListRecords
{
    protected static string $resource = SpotlightAttributeDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
