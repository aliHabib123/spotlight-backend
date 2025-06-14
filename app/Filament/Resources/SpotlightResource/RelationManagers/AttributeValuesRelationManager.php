<?php

namespace App\Filament\Resources\SpotlightResource\RelationManagers;

use App\Models\SpotlightAttributeDefinition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttributeValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'attributeValues';
    
    protected static ?string $title = 'Attribute Values';
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('attribute_definition_id')
                    ->label('Attribute')
                    ->options(function ($record) {
                        // Show only attributes applicable to this spotlight's category
                        $categoryId = $this->ownerRecord->category_id;
                        $attributeDefinitions = SpotlightAttributeDefinition::whereHas('categories', function ($query) use ($categoryId) {
                            $query->where('spotlight_categories.id', $categoryId);
                        })->get();
                        
                        return $attributeDefinitions->pluck('name', 'id')->toArray();
                    })
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('attribute_option_id', null);
                    }),
                    
                Forms\Components\Select::make('attribute_option_id')
                    ->label('Option')
                    ->options(function (callable $get) {
                        $definitionId = $get('attribute_definition_id');
                        if (!$definitionId) {
                            return [];
                        }
                        
                        $definition = SpotlightAttributeDefinition::find($definitionId);
                        if (!$definition || $definition->type !== 'enum') {
                            return [];
                        }
                        
                        return $definition->options->pluck('display_label', 'id')->toArray();
                    })
                    ->searchable()
                    ->visible(function (callable $get) {
                        $definitionId = $get('attribute_definition_id');
                        if (!$definitionId) {
                            return false;
                        }
                        
                        $definition = SpotlightAttributeDefinition::find($definitionId);
                        return $definition && $definition->type === 'enum';
                    }),
                    
                Forms\Components\TextInput::make('value')
                    ->label('Value')
                    ->visible(function (callable $get) {
                        $definitionId = $get('attribute_definition_id');
                        if (!$definitionId) {
                            return false;
                        }
                        
                        $definition = SpotlightAttributeDefinition::find($definitionId);
                        return $definition && $definition->type !== 'enum';
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('attributeDefinition.name')
                    ->label('Attribute')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('attributeDefinition.type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('display_value')
                    ->label('Value')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('value', 'like', "%{$search}%")
                            ->orWhereHas('attributeOption', function (Builder $query) use ($search) {
                                $query->where('value', 'like', "%{$search}%")
                                    ->orWhere('display_label', 'like', "%{$search}%");
                            });
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('attribute_definition_id')
                    ->label('Attribute')
                    ->options(function () {
                        // Show only attributes applicable to this spotlight's category
                        $categoryId = $this->ownerRecord->category_id;
                        $attributeDefinitions = SpotlightAttributeDefinition::whereHas('categories', function ($query) use ($categoryId) {
                            $query->where('spotlight_categories.id', $categoryId);
                        })->get();
                        
                        return $attributeDefinitions->pluck('name', 'id')->toArray();
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
