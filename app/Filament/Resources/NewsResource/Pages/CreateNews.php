<?php

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateNews extends CreateRecord
{
    protected static string $resource = NewsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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
