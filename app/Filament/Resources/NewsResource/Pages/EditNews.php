<?php

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNews extends EditRecord
{
    protected static string $resource = NewsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $sendNotification = array_key_exists('send_notification', $data)
            ? (bool) $data['send_notification']
            : true;

        session(['news_send_notification' => $sendNotification]);

        if (isset($data['send_notification'])) {
            unset($data['send_notification']);
        }

        return $data;
    }
}
