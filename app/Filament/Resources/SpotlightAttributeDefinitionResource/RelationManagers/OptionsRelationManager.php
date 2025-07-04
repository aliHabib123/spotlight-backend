<?php

namespace App\Filament\Resources\SpotlightAttributeDefinitionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';
    
    protected static ?string $title = 'Attribute Options';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->type === 'enum';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('value')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\TextInput::make('display_label')
                    ->maxLength(255)
                    ->helperText('Optional. If not provided, the value will be used as the label.'),
                    
                Forms\Components\TextInput::make('meta_data')
                    ->maxLength(255)
                    ->helperText('Optional additional data for this option (e.g., hex color)'),
                    
                Forms\Components\TextInput::make('display_order')
                    ->numeric()
                    ->default(0)
                    ->helperText('Options are displayed in ascending order'),
                    
                Forms\Components\Select::make('parent_option_id')
                    ->label('Parent Option')
                    ->relationship('parentOption', 'value', function ($query, $record) {
                        // Only show options from the same attribute definition
                        // Exclude the current option and its children to prevent circular references
                        if ($record) {
                            // Get all descendant IDs to avoid circular references
                            $excludeIds = [$record->id];
                            $childIds = $record->getAllChildrenIds();
                            $excludeIds = array_merge($excludeIds, $childIds);
                            return $query->whereNotIn('id', $excludeIds);
                        }
                        return $query;
                    })
                    ->searchable()
                    ->preload()
                    ->placeholder('No parent (top-level option)')
                    ->helperText('Select a parent option to create a dependent relationship (e.g., District belongs to Governorate)'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('value')
            ->columns([
                Tables\Columns\TextColumn::make('value')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('display_label')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('meta_data')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('display_order')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('values_count')
                    ->counts('attributeValues')
                    ->label('Usage')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('importOptions')
                    ->label('Import Options')
                    ->icon('heroicon-o-document-plus')
                    ->form([
                        Forms\Components\Textarea::make('options')
                            ->label('Options (one per line)')
                            ->required()
                            ->helperText('Enter one option per line. You can use format "value|display_label" to specify a display label.'),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        $lines = explode("\n", $data['options']);
                        $order = $livewire->getOwnerRecord()->options()->max('display_order') ?? 0;
                        
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (empty($line)) continue;
                            
                            $order++;
                            
                            if (str_contains($line, '|')) {
                                [$value, $label] = explode('|', $line, 2);
                                $livewire->getOwnerRecord()->options()->create([
                                    'value' => trim($value),
                                    'display_label' => trim($label),
                                    'display_order' => $order,
                                ]);
                            } else {
                                $livewire->getOwnerRecord()->options()->create([
                                    'value' => $line,
                                    'display_order' => $order,
                                ]);
                            }
                        }
                    }),
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
            ->defaultSort('display_order');
    }
}
