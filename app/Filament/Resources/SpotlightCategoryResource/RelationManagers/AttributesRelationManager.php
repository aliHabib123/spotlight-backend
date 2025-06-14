<?php

namespace App\Filament\Resources\SpotlightCategoryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\SpotlightAttributeDefinition;

class AttributesRelationManager extends RelationManager
{
    protected static string $relationship = 'attributeDefinitions';

    protected static ?string $recordTitleAttribute = 'name';
    
    protected static ?string $title = 'Category Attributes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('attribute_definition_id')
                    ->label('Attribute')
                    ->options(SpotlightAttributeDefinition::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($record) => $record !== null)
                    ->hiddenOn('edit'),
                
                Forms\Components\TextInput::make('display_order')
                    ->integer()
                    ->default(0)
                    ->minValue(0)
                    ->required(),
                
                Forms\Components\Toggle::make('is_required')
                    ->label('Required')
                    ->helperText('Override the default required setting for this category')
                    ->default(false),
                    
                Forms\Components\Toggle::make('is_featured')
                    ->label('Featured')
                    ->helperText('Show this attribute prominently in the UI')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Attribute')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'string' => 'Text',
                        'number' => 'Number',
                        'boolean' => 'Boolean',
                        'enum' => 'Enumeration',
                        'date' => 'Date',
                        'time' => 'Time',
                        'datetime' => 'Date & Time',
                        default => $state,
                    }),
                
                Tables\Columns\IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean(),
                    
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('display_order')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'string' => 'Text',
                        'number' => 'Number',
                        'boolean' => 'Boolean',
                        'enum' => 'Enumeration',
                        'date' => 'Date',
                        'time' => 'Time',
                        'datetime' => 'Date & Time',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_required')
                    ->label('Required Attributes')
                    ->placeholder('All Attributes')
                    ->trueLabel('Required Only')
                    ->falseLabel('Optional Only')
                    ->native(false),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\Toggle::make('is_required')
                            ->label('Required')
                            ->default(false),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured')
                            ->default(false),
                        Forms\Components\TextInput::make('display_order')
                            ->integer()
                            ->default(0),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ])
            ->defaultSort('spotlight_category_attributes.display_order');
    }
}
