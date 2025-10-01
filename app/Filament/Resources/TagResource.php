<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TagResource\Pages;
use App\Models\Tag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Spotlights';

    protected static ?int $navigationSort = 20;

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
                Forms\Components\Section::make('Tag Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                                $operation === 'create' ? $set('slug', Str::slug($state)) : null
                            ),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Tag::class, 'slug', ignoreRecord: true)
                            ->rules(['alpha_dash'])
                            ->helperText('Auto-generated from name if left empty.'),

                        Forms\Components\Hidden::make('type')
                            ->default('general'),

                        Forms\Components\Hidden::make('color')
                            ->default('#ffffff'),

                        Forms\Components\TextInput::make('display_order')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->label('Category')
                            ->preload()
                            ->searchable()
                            ->helperText('Assign this tag to a specific category to filter tags on the spotlight form.'),
                    ]),
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
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => Str::title($state)),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                // Tables\Columns\ColorColumn::make('color')
                //     ->toggleable(),

                Tables\Columns\TextColumn::make('spotlights_count')
                    ->counts('spotlights')
                    ->label('Spotlights')
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_order')
                    ->numeric()
                    ->sortable(),

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
                // Tables\Filters\SelectFilter::make('type')
                //     ->options([
                //         'general' => 'General',
                //         'amenity' => 'Amenity',
                //         'cuisine' => 'Cuisine',
                //         'feature' => 'Feature',
                //         'style' => 'Style',
                //         'season' => 'Season',
                //     ]),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->preload()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('changeType')
                        ->label('Change Type')
                        ->icon('heroicon-o-tag')
                        ->form([
                            Forms\Components\Select::make('type')
                                ->options([
                                    'general' => 'General',
                                    'amenity' => 'Amenity',
                                    'cuisine' => 'Cuisine',
                                    'feature' => 'Feature',
                                    'style' => 'Style',
                                    'season' => 'Season',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records): void {
                            foreach ($records as $record) {
                                $record->update(['type' => $data['type']]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('display_order');
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
            'index' => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'edit' => Pages\EditTag::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
