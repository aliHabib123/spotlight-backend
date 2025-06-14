<?php

namespace App\Filament\Resources\SpotlightResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';
    
    protected static ?string $title = 'Media';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('file_path')
                    ->label('Media File')
                    ->image()
                    ->imageEditor()
                    ->required()
                    ->maxSize(10240) // 10MB max
                    ->directory('spotlight-media')
                    ->visibility('public'),
                    
                Forms\Components\TextInput::make('title')
                    ->maxLength(255),
                    
                Forms\Components\Textarea::make('description')
                    ->maxLength(1000),
                    
                Forms\Components\Select::make('type')
                    ->options([
                        'image' => 'Image',
                        'video' => 'Video',
                        'document' => 'Document',
                        'audio' => 'Audio',
                    ])
                    ->required()
                    ->default('image'),
                    
                Forms\Components\TextInput::make('alt_text')
                    ->maxLength(255)
                    ->helperText('Alternative text for accessibility'),
                    
                Forms\Components\KeyValue::make('meta_data')
                    ->keyLabel('Property')
                    ->valueLabel('Value')
                    ->helperText('Additional metadata for this media item'),
                    
                Forms\Components\Toggle::make('is_featured')
                    ->label('Featured')
                    ->helperText('Featured media appears in prominent locations')
                    ->default(false),
                    
                Forms\Components\TextInput::make('display_order')
                    ->integer()
                    ->default(0)
                    ->helperText('Media items are displayed in ascending order'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('Media')
                    ->square(),
                    
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                    
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('display_order')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'image' => 'Image',
                        'video' => 'Video',
                        'document' => 'Document',
                        'audio' => 'Audio',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured Media')
                    ->placeholder('All Media')
                    ->trueLabel('Featured Only')
                    ->falseLabel('Non-Featured Only')
                    ->native(false),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('uploadMultiple')
                    ->label('Upload Multiple')
                    ->icon('heroicon-o-photo')
                    ->form([
                        Forms\Components\FileUpload::make('files')
                            ->multiple()
                            ->image()
                            ->required()
                            ->maxSize(10240) // 10MB max
                            ->directory('spotlight-media')
                            ->visibility('public'),
                            
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Mark All as Featured')
                            ->default(false),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        $order = $livewire->getRelationship()->max('display_order') ?? 0;
                        
                        foreach ($data['files'] as $file) {
                            $order++;
                            $livewire->getRelationship()->create([
                                'file_path' => $file,
                                'type' => 'image',
                                'is_featured' => $data['is_featured'],
                                'display_order' => $order,
                            ]);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('toggleFeatured')
                    ->label('Toggle Featured')
                    ->icon('heroicon-o-star')
                    ->action(fn ($record) => $record->update(['is_featured' => !$record->is_featured])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markAsFeatured')
                        ->label('Mark as Featured')
                        ->icon('heroicon-o-star')
                        ->action(fn (Builder $query) => $query->update(['is_featured' => true])),
                    Tables\Actions\BulkAction::make('unmarkAsFeatured')
                        ->label('Unmark as Featured')
                        ->icon('heroicon-o-no-symbol')
                        ->action(fn (Builder $query) => $query->update(['is_featured' => false])),
                ]),
            ])
            ->defaultSort('display_order')
            ->reorderable('display_order');
    }
}
