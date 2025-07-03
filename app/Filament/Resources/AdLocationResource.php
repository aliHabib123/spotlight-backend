<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdLocationResource\Pages;
use App\Filament\Resources\AdLocationResource\RelationManagers;
use App\Models\AdLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Facades\FilamentIcon;
use Filament\Facades\Filament;

class AdLocationResource extends Resource
{
    protected static ?string $model = AdLocation::class;

    public static function canAccess(): bool
    {
        // Only super-admin users can manage ad locations
        if (!Auth::check()) {
            return false;
        }
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if the user has the super-admin role
        return $user->hasRole('super-admin');
    }

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';
    
    protected static ?string $navigationGroup = 'Content Management';
    
    protected static ?int $navigationSort = 20;
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ad Location Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('slug')
                            ->maxLength(255)
                            ->helperText('Leave empty to auto-generate from name')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('ads_count')
                    ->label('Ads')
                    ->counts('ads')
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AdsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdLocations::route('/'),
            'create' => Pages\CreateAdLocation::route('/create'),
            'edit' => Pages\EditAdLocation::route('/{record}/edit'),
        ];
    }
    
    public static function getModelLabel(): string
    {
        return 'Ad Location';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Ad Locations';
    }
}
