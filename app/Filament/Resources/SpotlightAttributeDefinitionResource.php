<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpotlightAttributeDefinitionResource\Pages;
use App\Filament\Resources\SpotlightAttributeDefinitionResource\RelationManagers;
use App\Models\SpotlightAttributeDefinition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SpotlightAttributeDefinitionResource extends Resource
{
    protected static ?string $model = SpotlightAttributeDefinition::class;

    public static function canAccess(): bool
    {
        // Only super admin and admin users can manage attribute definitions
        if (!Auth::check()) {
            return false;
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if the user has the super admin or admin role
        return $user->hasRole(['super admin', 'admin']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationLabel = 'Attribute Definitions';

    protected static ?string $navigationGroup = 'Spotlights';

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attribute Definition')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(SpotlightAttributeDefinition::class, 'slug', ignoreRecord: true)
                            ->rules(['alpha_dash']),

                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'string' => 'Text (String)',
                                'number' => 'Number',
                                'boolean' => 'Boolean (Yes/No)',
                                'enum' => 'Enumeration (Select)',
                                'date' => 'Date',
                                'time' => 'Time',
                                'datetime' => 'Date and Time',
                            ])
                            ->live(),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000),
                        
                        Forms\Components\Select::make('parent_id')
                            ->label('Parent Attribute')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('No parent (top-level attribute)')
                            ->helperText('Select a parent to create a dependent attribute (e.g., District depends on Governorate)'),

                        Forms\Components\KeyValue::make('validation_rules')
                            ->keyLabel('Rule')
                            ->valueLabel('Parameter')
                            ->helperText('e.g. "min" => "0", "max" => "100", "regex" => "pattern"')
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['string', 'number']))
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_required')
                            ->label('Required')
                            ->default(false),

                        Forms\Components\Toggle::make('is_filterable')
                            ->label('Filterable')
                            ->helperText('Can be used as a filter in search queries')
                            ->default(false),

                        Forms\Components\Toggle::make('allows_multiple')
                            ->label('Multiple Values')
                            ->helperText('Allow multiple values for this attribute')
                            ->default(false)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'enum'),

                        Forms\Components\Select::make('display_type')
                            ->label('UI Control Type')
                            ->options([
                                'text' => 'Text Input',
                                'textarea' => 'Text Area',
                                'select' => 'Dropdown Select',
                                'multiselect' => 'Multi Select',
                                'checkbox' => 'Checkbox',
                                'radio' => 'Radio Buttons',
                                'toggle' => 'Toggle Switch',
                                'slider' => 'Slider',
                                'date' => 'Date Picker',
                                'time' => 'Time Picker',
                                'datetime' => 'Date Time Picker',
                            ])
                            ->helperText('Override the default form control type'),

                        Forms\Components\TextInput::make('display_order')
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query->withCount(['categories', 'options', 'values']);
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

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

                Tables\Columns\IconColumn::make('is_filterable')
                    ->label('Filterable')
                    ->boolean(),

                Tables\Columns\IconColumn::make('allows_multiple')
                    ->label('Multiple')
                    ->boolean(),

                Tables\Columns\TextColumn::make('categories_count')
                    ->label('Categories')
                    ->formatStateUsing(fn ($state) => $state ?? 0),

                Tables\Columns\TextColumn::make('options_count')
                    ->label('Options')
                    ->formatStateUsing(fn ($state) => $state ?? 0)
                    ->visible(fn ($record) => $record?->type === 'enum'),

                Tables\Columns\TextColumn::make('display_order')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

                Tables\Filters\TernaryFilter::make('is_filterable')
                    ->label('Filterable Attributes')
                    ->placeholder('All Attributes')
                    ->trueLabel('Filterable Only')
                    ->falseLabel('Non-Filterable Only')
                    ->native(false),
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\OptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpotlightAttributeDefinitions::route('/'),
            'create' => Pages\CreateSpotlightAttributeDefinition::route('/create'),
            'edit' => Pages\EditSpotlightAttributeDefinition::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return cache()->remember('spotlight_attribute_definitions_count', 300, function () {
            return static::getModel()::count();
        });
    }
}
