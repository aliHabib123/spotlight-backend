<?php

namespace App\Filament\Resources\AdLocationResource\Pages;

use App\Filament\Resources\AdLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdLocation extends EditRecord
{
    protected static string $resource = AdLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
