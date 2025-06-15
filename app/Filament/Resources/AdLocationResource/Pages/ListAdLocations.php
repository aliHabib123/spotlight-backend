<?php

namespace App\Filament\Resources\AdLocationResource\Pages;

use App\Filament\Resources\AdLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdLocations extends ListRecords
{
    protected static string $resource = AdLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
