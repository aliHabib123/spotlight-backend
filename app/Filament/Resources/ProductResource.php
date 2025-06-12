<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    
    protected static ?string $navigationGroup = 'Shop Management';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('shop_id')
                            ->relationship('shop', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => 
                                $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),
                        
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                            
                        Forms\Components\Select::make('type')
                            ->options([
                                'single' => 'Single Product',
                                'variable' => 'Variable Product',
                            ])
                            ->default('single')
                            ->required()
                            ->live(),
                            
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Pricing & Inventory')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Regular Price')
                            ->numeric()
                            ->prefix('$')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'single'),
                            
                        Forms\Components\TextInput::make('sale_price')
                            ->label('Sale Price')
                            ->numeric()
                            ->prefix('$')
                            ->lte('price')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'single'),
                            
                        Forms\Components\Toggle::make('on_sale')
                            ->label('On Sale')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'single'),
                            
                        Forms\Components\TextInput::make('stock')
                            ->label('Stock Quantity')
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'single'),
                            
                        Forms\Components\Placeholder::make('variable_product_notice')
                            ->content('Pricing and inventory for variable products are managed in the Variations tab.')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'variable')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Images')
                    ->schema([
                        Forms\Components\FileUpload::make('images')
                            ->image()
                            ->multiple()
                            ->directory('products')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Attributes')
                    ->schema([
                        Forms\Components\KeyValue::make('attributes')
                            ->keyLabel('Attribute Name')
                            ->valueLabel('Values (comma separated)')
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'variable')
                            ->helperText('For variable products, add attributes like Color, Size, etc. Values should be comma-separated.'),
                    ]),
                    
                Forms\Components\Section::make('Display Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                            
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shop.name')
                    ->label('Shop')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'single' => 'success',
                        'variable' => 'info',
                    }),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable()
                    ->visible(fn (Tables\Columns\Column $column): bool => 
                        $column->getTable()->getModel()::query()->where('type', 'single')->exists()),
                Tables\Columns\IconColumn::make('on_sale')
                    ->boolean()
                    ->label('Sale')
                    ->sortable()
                    ->visible(fn (Tables\Columns\Column $column): bool => 
                        $column->getTable()->getModel()::query()->where('type', 'single')->exists()),
                Tables\Columns\TextColumn::make('stock')
                    ->sortable()
                    ->visible(fn (Tables\Columns\Column $column): bool => 
                        $column->getTable()->getModel()::query()->where('type', 'single')->exists()),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shop')
                    ->relationship('shop', 'name'),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'single' => 'Single Product',
                        'variable' => 'Variable Product',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All Products')
                    ->trueLabel('Active Products')
                    ->falseLabel('Inactive Products'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured')
                    ->placeholder('All Products')
                    ->trueLabel('Featured Products')
                    ->falseLabel('Non-Featured Products'),
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
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_active' => false])),
                    Tables\Actions\BulkAction::make('feature')
                        ->label('Mark as Featured')
                        ->icon('heroicon-o-star')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_featured' => true])),
                    Tables\Actions\BulkAction::make('unfeature')
                        ->label('Remove Featured')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_featured' => false])),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductVariationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
