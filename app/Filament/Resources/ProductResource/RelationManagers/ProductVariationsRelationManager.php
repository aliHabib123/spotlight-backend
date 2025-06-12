<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\ProductVariation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductVariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Variation Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\KeyValue::make('attributes')
                            ->keyLabel('Attribute')
                            ->valueLabel('Value')
                            ->required()
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Pricing & Inventory')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Regular Price')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                            
                        Forms\Components\TextInput::make('sale_price')
                            ->label('Sale Price')
                            ->numeric()
                            ->prefix('$')
                            ->lte('price'),
                            
                        Forms\Components\Toggle::make('on_sale')
                            ->label('On Sale'),
                            
                        Forms\Components\TextInput::make('stock')
                            ->label('Stock Quantity')
                            ->numeric()
                            ->minValue(0),
                            
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Image')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->image()
                            ->directory('product-variations')
                            ->maxSize(1024),
                    ]),
                    
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attributes')
                    ->badge()
                    ->formatStateUsing(fn (array $state): array => collect($state)
                        ->map(fn ($value, $key) => "$key: $value")
                        ->toArray()),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\IconColumn::make('on_sale')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All Variations')
                    ->trueLabel('Active Variations')
                    ->falseLabel('Inactive Variations'),
                Tables\Filters\TernaryFilter::make('on_sale')
                    ->label('On Sale')
                    ->placeholder('All Variations')
                    ->trueLabel('On Sale')
                    ->falseLabel('Regular Price'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn (RelationManager $livewire) => $livewire->getOwnerRecord()->isVariable()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->action(fn (Builder $query) => $query->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn (Builder $query) => $query->update(['is_active' => false])),
                ]),
            ]);
    }
}
