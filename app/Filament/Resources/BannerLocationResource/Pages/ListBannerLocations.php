<?php

namespace App\Filament\Resources\BannerLocationResource\Pages;

use App\Filament\Resources\BannerLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBannerLocations extends ListRecords
{
    protected static string $resource = BannerLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
