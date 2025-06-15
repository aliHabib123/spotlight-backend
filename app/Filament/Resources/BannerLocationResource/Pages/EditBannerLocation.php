<?php

namespace App\Filament\Resources\BannerLocationResource\Pages;

use App\Filament\Resources\BannerLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBannerLocation extends EditRecord
{
    protected static string $resource = BannerLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
