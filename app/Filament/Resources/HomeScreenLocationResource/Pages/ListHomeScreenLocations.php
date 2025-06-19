<?php

namespace App\Filament\Resources\HomeScreenLocationResource\Pages;

use App\Filament\Resources\HomeScreenLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomeScreenLocations extends ListRecords
{
    protected static string $resource = HomeScreenLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
