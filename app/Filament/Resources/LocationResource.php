<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    
    protected static ?string $navigationGroup = 'Spotlights';
    
    protected static ?int $navigationSort = 30;

    public static function canAccess(): bool
    {
        // Only admins and super admins can access this resource
        if (Auth::check()) {
            $user = Auth::user();
            return $user->hasAnyRole(['super admin', 'admin']);
        }
        return false;
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Location Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('city')
                            ->required()
                            ->maxLength(100),
                            
                        // Hidden field with default value since it's required in the database
                        Forms\Components\Hidden::make('country')
                            ->default('Lebanon'),
                    ]),
                    
                // Geographic Coordinates section hidden for now - for future implementation
                /* 
                Forms\Components\Section::make('Geographic Coordinates')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->inputMode('decimal'),
                                    
                                Forms\Components\TextInput::make('longitude')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->inputMode('decimal'),
                            ]),
                            
                        Forms\Components\KeyValue::make('additional_info')
                            ->keyLabel('Property')
                            ->valueLabel('Value')
                            ->helperText('Add any additional location information required.')
                            ->columnSpanFull(),
                    ]),
                */
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('full_address')
                    ->label('Full Address')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('address_line_1', 'like', "%{$search}%")
                            ->orWhere('address_line_2', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%")
                            ->orWhere('state', 'like', "%{$search}%")
                            ->orWhere('postal_code', 'like', "%{$search}%")
                            ->orWhere('country', 'like', "%{$search}%");
                    })
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('spotlights_count')
                    ->counts('spotlights')
                    ->label('Spotlights'),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('city')
                    ->label('City')
                    ->form([
                        Forms\Components\TextInput::make('city')
                            ->label('City')
                            ->placeholder('Search by city'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['city'],
                            fn ($query, $city) => $query->where('city', 'like', "%{$city}%")
                        );
                    }),
                    
                Tables\Filters\Filter::make('country')
                    ->label('Country')
                    ->form([
                        Forms\Components\TextInput::make('country')
                            ->label('Country')
                            ->placeholder('Search by country')
                            ->default('Lebanon'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['country'],
                            fn ($query, $country) => $query->where('country', 'like', "%{$country}%")
                        );
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
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
