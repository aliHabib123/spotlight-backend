<?php

namespace App\Filament\Resources\SpotlightCategoryResource\Pages;

use App\Filament\Resources\SpotlightCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSpotlightCategory extends EditRecord
{
    protected static string $resource = SpotlightCategoryResource::class;

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
