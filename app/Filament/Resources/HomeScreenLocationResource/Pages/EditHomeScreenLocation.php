<?php

namespace App\Filament\Resources\HomeScreenLocationResource\Pages;

use App\Filament\Resources\HomeScreenLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHomeScreenLocation extends EditRecord
{
    protected static string $resource = HomeScreenLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
