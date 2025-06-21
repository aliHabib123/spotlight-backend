<?php

namespace App\Filament\Resources\AboutUsResource\Pages;

use App\Filament\Resources\AboutUsResource;
use App\Models\AboutUs;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageAboutUs extends ManageRecords
{
    protected static string $resource = AboutUsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn() => AboutUs::count() === 0),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Get the form context based on whether we're creating or editing
    protected function getFormContext(): string
    {
        return $this->hasRecord() ? 'edit' : 'create';
    }

    // Get the form model for the page
    protected function getFormModel(): Model|string|null
    {
        return $this->hasRecord()
            ? $this->getRecord()
            : $this->getModel();
    }

    // Check if we have a record to edit
    protected function hasRecord(): bool
    {
        return AboutUs::count() > 0;
    }

    // Get the record to edit
    protected function getRecord(): ?Model
    {
        return AboutUs::first();
    }
}
