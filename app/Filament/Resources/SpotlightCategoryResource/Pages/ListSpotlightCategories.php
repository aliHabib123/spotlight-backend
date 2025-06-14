<?php

namespace App\Filament\Resources\SpotlightCategoryResource\Pages;

use App\Filament\Resources\SpotlightCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSpotlightCategories extends ListRecords
{
    protected static string $resource = SpotlightCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
