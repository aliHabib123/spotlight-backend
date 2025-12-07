<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TourResource\Pages;
use App\Filament\Resources\TourResource\RelationManagers;
use App\Filament\Resources\TourResource\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\TourResource\RelationManagers\DayAvailabilitiesRelationManager;
use App\Filament\Resources\TourResource\RelationManagers\DateRangesRelationManager;
use App\Models\Tour;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Colors;

class TourResource extends Resource
{
    protected static ?string $model = Tour::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Tours Management';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        // Tour resources should be visible to admins, super admins and tour admins
        if (Auth::check()) {
            $user = Auth::user();
            return $user->hasAnyRole(['super admin', 'admin', 'tour admin']);
        }
        return false;
    }

    // Keep for backward compatibility
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->description('**Note:** To add or manage tour images, please save this form and use the "Images" tab that appears at the top of the page.')
                    ->columnSpanFull(),
                Forms\Components\Tabs::make('Tour Information')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Information')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                        if ($operation !== 'create') {
                                            return;
                                        }

                                        $set('slug', \Illuminate\Support\Str::slug($state));
                                    }),
                                Forms\Components\TextInput::make('slug')
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText('Auto-generated from title, can be customized if needed'),
                                Forms\Components\Select::make('tour_location_id')
                                    ->relationship('location', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\RichEditor::make('description')
                                    ->required()
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Pricing')
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('Adult Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                                Forms\Components\TextInput::make('kids_price')
                                    ->label('Kids Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->helperText('Leave empty if not applicable'),
                                Forms\Components\TextInput::make('infant_price')
                                    ->label('Infant Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->helperText('Leave empty if not applicable'),
                                Forms\Components\TextInput::make('capacity')
                                    ->label('Max Capacity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->step(1)
                                    ->helperText('Maximum number of people allowed on the tour'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Availability')
                            ->schema([
                                Forms\Components\Section::make('Select Available Days')
                                    ->description('Please select at least one day')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Checkbox::make('available_days.monday')
                                                    ->label('Monday')
                                                    ->afterStateHydrated(function ($state, callable $set, $record) {
                                                        if ($record) {
                                                            $days = $record->dayAvailabilities()->pluck('day')->toArray();
                                                            $set('available_days.monday', in_array('monday', $days));
                                                        }
                                                    }),
                                                Forms\Components\Checkbox::make('available_days.tuesday')
                                                    ->label('Tuesday')
                                                    ->afterStateHydrated(function ($state, callable $set, $record) {
                                                        if ($record) {
                                                            $days = $record->dayAvailabilities()->pluck('day')->toArray();
                                                            $set('available_days.tuesday', in_array('tuesday', $days));
                                                        }
                                                    }),
                                                Forms\Components\Checkbox::make('available_days.wednesday')
                                                    ->label('Wednesday')
                                                    ->afterStateHydrated(function ($state, callable $set, $record) {
                                                        if ($record) {
                                                            $days = $record->dayAvailabilities()->pluck('day')->toArray();
                                                            $set('available_days.wednesday', in_array('wednesday', $days));
                                                        }
                                                    }),
                                                Forms\Components\Checkbox::make('available_days.thursday')
                                                    ->label('Thursday')
                                                    ->afterStateHydrated(function ($state, callable $set, $record) {
                                                        if ($record) {
                                                            $days = $record->dayAvailabilities()->pluck('day')->toArray();
                                                            $set('available_days.thursday', in_array('thursday', $days));
                                                        }
                                                    }),
                                                Forms\Components\Checkbox::make('available_days.friday')
                                                    ->label('Friday')
                                                    ->afterStateHydrated(function ($state, callable $set, $record) {
                                                        if ($record) {
                                                            $days = $record->dayAvailabilities()->pluck('day')->toArray();
                                                            $set('available_days.friday', in_array('friday', $days));
                                                        }
                                                    }),
                                                Forms\Components\Checkbox::make('available_days.saturday')
                                                    ->label('Saturday')
                                                    ->afterStateHydrated(function ($state, callable $set, $record) {
                                                        if ($record) {
                                                            $days = $record->dayAvailabilities()->pluck('day')->toArray();
                                                            $set('available_days.saturday', in_array('saturday', $days));
                                                        }
                                                    }),
                                                Forms\Components\Checkbox::make('available_days.sunday')
                                                    ->label('Sunday')
                                                    ->afterStateHydrated(function ($state, callable $set, $record) {
                                                        if ($record) {
                                                            $days = $record->dayAvailabilities()->pluck('day')->toArray();
                                                            $set('available_days.sunday', in_array('sunday', $days));
                                                        }
                                                    }),
                                            ]),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        // We're not using the inline image tab - use relation manager instead
                        // Forms\Components\Tabs\Tab::make('Images')
                        //     ->schema([
                        //     ]),

                        // For admins - full management tab
                        Forms\Components\Tabs\Tab::make('Management')
                            ->schema([
                                Forms\Components\Toggle::make('active')
                                    ->label('Approve Tour')
                                    ->helperText('Tours require approval by an admin before they become visible')
                                    ->default(false)
                                    // Allow toggling only for admins and super admins
                                    ->disabled(fn () => !(Auth::check() && (Auth::user()->hasRole('super admin') || Auth::user()->hasRole('admin')))),
                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Featured')
                                    ->helperText('Feature this tour to highlight it in the app')
                                    ->default(false)
                                    // Only visible to admins and super admins
                                    ->visible(fn () => Auth::check() && (Auth::user()->hasRole('super admin') || Auth::user()->hasRole('admin'))),
                                Forms\Components\Toggle::make('send_notification')
                                    ->label('Send Push Notification')
                                    ->helperText('Send a push notification to all app users when this tour is approved')
                                    ->default(true)
                                    // Only visible to admins and super admins
                                    ->visible(fn () => Auth::check() && (Auth::user()->hasRole('super admin') || Auth::user()->hasRole('admin'))),
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    // Only visible to admins and super admins
                                    ->visible(fn () => Auth::check() && (Auth::user()->hasRole('super admin') || Auth::user()->hasRole('admin'))),
                                Forms\Components\TextInput::make('display_order')
                                    ->numeric()
                                    ->default(0)
                                    // Only visible to admins and super admins
                                    ->visible(fn () => Auth::check() && (Auth::user()->hasRole('super admin') || Auth::user()->hasRole('admin'))),
                            ])
                            ->columns(2)
                            // Change the label based on user role
                            ->label(fn () => Auth::check() && Auth::user()->hasRole('tour admin') ? 'Status' : 'Management'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('capacity')
                    ->sortable(),
                Tables\Columns\TextColumn::make('active')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Approved' : 'Pending Approval')
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('display_order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->searchable()
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
                Tables\Filters\SelectFilter::make('tour_location_id')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Location'),
                Tables\Filters\SelectFilter::make('active')
                    ->options([
                        '1' => 'Approved',
                        '0' => 'Pending Approval',
                    ])
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('is_featured')
                    ->options([
                        '1' => 'Featured',
                        '0' => 'Not Featured',
                    ])
                    ->label('Featured'),
                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price_from')
                                    ->label('Min Price')
                                    ->numeric()
                                    ->placeholder('From'),
                                Forms\Components\TextInput::make('price_to')
                                    ->label('Max Price')
                                    ->numeric()
                                    ->placeholder('To'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $price): Builder => $query->where('price', '>=', $price),
                            )
                            ->when(
                                $data['price_to'],
                                fn (Builder $query, $price): Builder => $query->where('price', '<=', $price),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleApproval')
                    ->label(fn (Tour $record): string => $record->active ? 'Disapprove' : 'Approve')
                    ->icon(fn (Tour $record): string => $record->active ? 'heroicon-o-x-mark' : 'heroicon-o-check')
                    ->color(fn (Tour $record): string => $record->active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->visible(fn () => Auth::check() && (Auth::user()->hasRole('super admin') || Auth::user()->hasRole('admin')))
                    ->action(function (Tour $record): void {
                        $record->active = !$record->active;
                        $record->save();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::check() && (Auth::user()->hasRole('super admin') || Auth::user()->hasRole('admin'))),
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn () => Auth::check() && (Auth::user()->hasRole('super admin') || Auth::user()->hasRole('admin')))
                        ->action(fn (Collection $records) => $records->each(function ($record) {
                            $record->active = true;
                            $record->save();
                        }))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('disapprove')
                        ->label('Disapprove Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn () => Auth::check() && (Auth::user()->hasRole('super admin') || Auth::user()->hasRole('admin')))
                        ->action(fn (Collection $records) => $records->each(function ($record) {
                            $record->active = false;
                            $record->save();
                        }))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // If the user is a tour admin, only show their own tours
        if (Auth::check() && Auth::user()->hasRole('tour admin')) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
            DayAvailabilitiesRelationManager::class,
            DateRangesRelationManager::class,
            // Removed RatingsRelationManager - ratings should be handled through the API only
            // RatingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTours::route('/'),
            'create' => Pages\CreateTour::route('/create'),
            'edit' => Pages\EditTour::route('/{record}/edit'),
        ];
    }
}
