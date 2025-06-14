<?php

namespace App\Filament\Resources\SpotlightCategoryResource\Pages;

use App\Filament\Resources\SpotlightCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSpotlightCategory extends CreateRecord
{
    protected static string $resource = SpotlightCategoryResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
