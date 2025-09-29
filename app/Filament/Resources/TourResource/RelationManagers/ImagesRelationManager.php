<?php

namespace App\Filament\Resources\TourResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';
    
    protected static ?string $title = 'Tour Images';
    
    protected static ?string $description = 'Manage tour images here. Upload multiple images, set their display order, and reorder them as needed.';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('image_path')
                    ->label('Image')
                    ->image()
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('16:9')
                    ->directory('tours')
                    ->imagePreviewHeight('250')
                    ->panelAspectRatio('16:9')
                    ->maxSize(5120) // 5MB max
                    ->helperText('Upload JPEG, PNG or WebP images. Max size: 5MB.')
                    ->required(),
                Forms\Components\TextInput::make('display_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('image_path')
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Image')
                    ->size(150)
                    ->square(false),
                Tables\Columns\TextColumn::make('display_order')
                    ->sortable()
                    ->badge()
                    ->label('Order'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->label('Added On'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Image')
                    ->icon('heroicon-o-photo')
                    ->color('primary'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('display_order')
            ->defaultSort('display_order');
    }
}
