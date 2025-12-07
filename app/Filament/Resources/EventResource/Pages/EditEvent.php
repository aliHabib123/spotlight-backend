<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

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

        session(['event_send_notification' => $sendNotification]);

        if (isset($data['send_notification'])) {
            unset($data['send_notification']);
        }

        return $data;
    }
}
