<?php

namespace App\Filament\Resources\TourLocationResource\Pages;

use App\Filament\Resources\TourLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTourLocation extends EditRecord
{
    protected static string $resource = TourLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
