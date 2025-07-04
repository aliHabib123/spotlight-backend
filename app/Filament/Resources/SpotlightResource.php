<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpotlightResource\Pages;
use App\Filament\Resources\SpotlightResource\RelationManagers;
use App\Models\Spotlight;
use App\Models\SpotlightCategory;
use App\Models\SpotlightCategoryAttribute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SpotlightResource extends Resource
{
    protected static ?string $model = Spotlight::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Spotlights';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Spotlight')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Information')
                            ->schema([
                                Forms\Components\Section::make('Basic Details')
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
                                            ->unique(Spotlight::class, 'slug', ignoreRecord: true)
                                            ->rules(['alpha_dash']),

                                        Forms\Components\Select::make('category_id')
                                            ->relationship('category', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->helperText('Select from existing categories. New categories must be created in the Categories section.'),

                                        Forms\Components\Section::make('Category Attributes')
                                            ->schema(function (Forms\Get $get, $livewire) {
                                                $categoryId = $get('category_id');
                                                if (empty($categoryId)) {
                                                    return [
                                                        Forms\Components\Placeholder::make('no_attributes')
                                                            ->content('Select a category to view its specific attributes')
                                                    ];
                                                }

                                                // Get category attribute definitions
                                                $categoryAttributes = \App\Models\SpotlightCategoryAttribute::where('category_id', $categoryId)
                                                    ->with('attributeDefinition.options')
                                                    ->get();

                                                if ($categoryAttributes->isEmpty()) {
                                                    return [
                                                        Forms\Components\Placeholder::make('no_attributes')
                                                            ->content('This category has no specific attributes')
                                                    ];
                                                }

                                                $attributeFields = [];

                                                // Get existing attribute values if we're editing a record
                                                $existingValues = [];
                                                $record = $livewire->record;

                                                if ($record) {
                                                    $attributesByDef = $record->getAttributesByDefinition();
                                                }

                                                foreach ($categoryAttributes as $categoryAttribute) {
                                                    $definition = $categoryAttribute->attributeDefinition;
                                                    if (!$definition) continue;

                                                    $fieldType = $definition->getFormFieldType();
                                                    $isRequired = $categoryAttribute->is_required;

                                                    $fieldName = "attributes.{$definition->id}";

                                                    // Get current value if editing and value exists
                                                    $currentValue = null;
                                                    $currentOptionId = null;
                                                    $currentOptionIds = [];

                                                    if ($record && isset($attributesByDef[$definition->id]) && !empty($attributesByDef[$definition->id]['values'])) {
                                                        $values = $attributesByDef[$definition->id]['values'];

                                                        if ($fieldType === 'select' && isset($values[0]->option_id)) {
                                                            $currentOptionId = $values[0]->option_id;
                                                        } elseif ($fieldType === 'multiselect') {
                                                            foreach ($values as $value) {
                                                                if (isset($value->option_id)) {
                                                                    $currentOptionIds[] = $value->option_id;
                                                                }
                                                            }
                                                        } elseif (isset($values[0]->value)) {
                                                            $currentValue = $values[0]->value;

                                                            // Convert boolean string to actual boolean
                                                            if ($fieldType === 'boolean' || $fieldType === 'toggle') {
                                                                $currentValue = filter_var($currentValue, FILTER_VALIDATE_BOOLEAN);
                                                            }
                                                        }
                                                    }

                                                    // Create appropriate field type based on definition
                                                    switch($fieldType) {
                                                        case 'text':
                                                            $attributeFields[] = Forms\Components\TextInput::make($fieldName)
                                                                ->label($definition->name)
                                                                ->helperText($definition->description)
                                                                ->required($isRequired)
                                                                ->default($currentValue);
                                                            break;

                                                        case 'textarea':
                                                            $attributeFields[] = Forms\Components\Textarea::make($fieldName)
                                                                ->label($definition->name)
                                                                ->helperText($definition->description)
                                                                ->required($isRequired)
                                                                ->afterStateHydrated(function ($component, $state) use ($record, $definition) {
                                                                    if ($record) {
                                                                        $attributeValue = $record->attributeValues()
                                                                            ->where('attribute_definition_id', $definition->id)
                                                                            ->whereNull('attribute_option_id')
                                                                            ->first();

                                                                        if ($attributeValue) {
                                                                            $component->state($attributeValue->value);
                                                                        }
                                                                    }
                                                                });
                                                            break;

                                                        case 'number':
                                                            $attributeFields[] = Forms\Components\TextInput::make($fieldName)
                                                                ->label($definition->name)
                                                                ->helperText($definition->description)
                                                                ->numeric()
                                                                ->required($isRequired)
                                                                ->default($currentValue);
                                                            break;

                                                        case 'boolean':
                                                        case 'toggle':
                                                            $attributeFields[] = Forms\Components\Toggle::make($fieldName)
                                                                ->label($definition->name)
                                                                ->helperText($definition->description)
                                                                ->required($isRequired)
                                                                ->afterStateHydrated(function ($component, $state) use ($record, $definition) {
                                                                    if ($record) {
                                                                        $attributeValue = $record->attributeValues()
                                                                            ->where('attribute_definition_id', $definition->id)
                                                                            ->whereNull('attribute_option_id')
                                                                            ->first();

                                                                        if ($attributeValue) {
                                                                            // Convert string 'true'/'false' to boolean if needed
                                                                            $value = $attributeValue->value;
                                                                            if (is_string($value)) {
                                                                                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                                                                            }
                                                                            $component->state($value);
                                                                        }
                                                                    }
                                                                });
                                                            break;

                                                        case 'select':
                                                            $options = $definition->options->pluck('display_label', 'id')->toArray();

                                                            // Debug to verify option values
                                                            \Illuminate\Support\Facades\Log::debug('Select field options', [
                                                                'definition' => $definition->name,
                                                                'options' => $options,
                                                                'currentOptionId' => $currentOptionId,
                                                                'values' => $record ? ($attributesByDef[$definition->id]['values'] ?? []) : []
                                                            ]);

                                                            $attributeFields[] = Forms\Components\Select::make($fieldName)
                                                                ->label($definition->name)
                                                                ->helperText($definition->description)
                                                                ->options($options)
                                                                ->required($isRequired)
                                                                ->afterStateHydrated(function ($component, $state) use ($record, $definition) {
                                                                    if ($record) {
                                                                        $attributeValue = $record->attributeValues()
                                                                            ->where('attribute_definition_id', $definition->id)
                                                                            ->first();

                                                                        if ($attributeValue && $attributeValue->attribute_option_id) {
                                                                            $component->state($attributeValue->attribute_option_id);
                                                                        }
                                                                    }
                                                                });
                                                            break;

                                                        case 'multiselect':
                                                            $options = $definition->options->pluck('display_label', 'id')->toArray();
                                                            $attributeFields[] = Forms\Components\Select::make($fieldName)
                                                                ->label($definition->name)
                                                                ->helperText($definition->description)
                                                                ->options($options)
                                                                ->multiple()
                                                                ->required($isRequired)
                                                                ->afterStateHydrated(function ($component, $state) use ($record, $definition) {
                                                                    if ($record) {
                                                                        $attributeValues = $record->attributeValues()
                                                                            ->where('attribute_definition_id', $definition->id)
                                                                            ->whereNotNull('attribute_option_id')
                                                                            ->pluck('attribute_option_id')
                                                                            ->toArray();

                                                                        if (!empty($attributeValues)) {
                                                                            $component->state($attributeValues);
                                                                        }
                                                                    }
                                                                });
                                                            break;

                                                        case 'date':
                                                            $attributeFields[] = Forms\Components\DatePicker::make($fieldName)
                                                                ->label($definition->name)
                                                                ->helperText($definition->description)
                                                                ->required($isRequired)
                                                                ->afterStateHydrated(function ($component, $state) use ($record, $definition) {
                                                                    if ($record) {
                                                                        $attributeValue = $record->attributeValues()
                                                                            ->where('attribute_definition_id', $definition->id)
                                                                            ->whereNull('attribute_option_id')
                                                                            ->first();

                                                                        if ($attributeValue && $attributeValue->value) {
                                                                            $component->state($attributeValue->value);
                                                                        }
                                                                    }
                                                                });
                                                            break;

                                                        case 'time':
                                                            $attributeFields[] = Forms\Components\TimePicker::make($fieldName)
                                                                ->label($definition->name)
                                                                ->helperText($definition->description)
                                                                ->required($isRequired)
                                                                ->afterStateHydrated(function ($component, $state) use ($record, $definition) {
                                                                    if ($record) {
                                                                        $attributeValue = $record->attributeValues()
                                                                            ->where('attribute_definition_id', $definition->id)
                                                                            ->whereNull('attribute_option_id')
                                                                            ->first();

                                                                        if ($attributeValue && $attributeValue->value) {
                                                                            $component->state($attributeValue->value);
                                                                        }
                                                                    }
                                                                });
                                                            break;

                                                        case 'datetime':
                                                            $attributeFields[] = Forms\Components\DateTimePicker::make($fieldName)
                                                                ->label($definition->name)
                                                                ->helperText($definition->description)
                                                                ->required($isRequired)
                                                                ->afterStateHydrated(function ($component, $state) use ($record, $definition) {
                                                                    if ($record) {
                                                                        $attributeValue = $record->attributeValues()
                                                                            ->where('attribute_definition_id', $definition->id)
                                                                            ->whereNull('attribute_option_id')
                                                                            ->first();

                                                                        if ($attributeValue && $attributeValue->value) {
                                                                            $component->state($attributeValue->value);
                                                                        }
                                                                    }
                                                                });
                                                            break;

                                                        default:
                                                            $attributeFields[] = Forms\Components\TextInput::make($fieldName)
                                                                ->label($definition->name)
                                                                ->helperText($definition->description)
                                                                ->required($isRequired)
                                                                ->afterStateHydrated(function ($component, $state) use ($record, $definition) {
                                                                    if ($record) {
                                                                        $attributeValue = $record->attributeValues()
                                                                            ->where('attribute_definition_id', $definition->id)
                                                                            ->whereNull('attribute_option_id')
                                                                            ->first();

                                                                        if ($attributeValue) {
                                                                            $component->state($attributeValue->value);
                                                                        }
                                                                    }
                                                                });
                                                            break;
                                                    }
                                                }

                                                return $attributeFields;
                                            })
                                            ->columns(2)
                                            ->visible(fn (Forms\Get $get) => !empty($get('category_id'))),

                                        Forms\Components\Select::make('location_id')
                                            ->relationship('location', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Select from existing locations. New locations must be created in the Locations section.'),

                                        // Owner/creator field removed as requested

                                        // Short description field hidden as requested
                                        /* Forms\Components\RichEditor::make('short_description')
                                            ->columnSpanFull()
                                            ->maxLength(1000), */

                                        Forms\Components\RichEditor::make('description')
                                            ->columnSpanFull()
                                            ->required()
                                            ->maxLength(5000),
                                    ]),

                                Forms\Components\Section::make('Status & Features')
                                    ->schema([

                                        Forms\Components\Toggle::make('is_featured')
                                            ->label('Featured')
                                            ->helperText('Featured spotlights appear in featured sections')
                                            ->default(false),

                                        Forms\Components\Toggle::make('is_trending')
                                            ->label('Trending')
                                            ->helperText('Trending spotlights appear in trending sections')
                                            ->default(false),


                                        Forms\Components\Toggle::make('is_published')
                                            ->label('Published')
                                            ->helperText('Only published spotlights are visible to the public')
                                            ->default(true),
                                    ]),


                            ]),

                        Forms\Components\Tabs\Tab::make('Social Media')
                            ->schema([
                                // Contact Information section hidden as requested
                                /* Forms\Components\Section::make('Contact Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('contact_email')
                                            ->email()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('contact_phone')
                                            ->tel()
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('website')
                                            ->url()
                                            ->maxLength(255),
                                    ]), */

                                Forms\Components\Section::make('Social Media')
                                    ->schema([
                                        Forms\Components\Toggle::make('has_social_media')
                                            ->label('Add Social Media Links')
                                            ->helperText('Enable to add social media profiles')
                                            ->default(false)
                                            ->live(),

                                        Forms\Components\Repeater::make('social_media')
                                            ->schema([
                                                Forms\Components\Grid::make()
                                                    ->schema([
                                                        Forms\Components\Select::make('platform')
                                                            ->label('Platform')
                                                            ->options([
                                                                'instagram' => 'Instagram',
                                                                'facebook' => 'Facebook',
                                                                'twitter' => 'Twitter',
                                                                'youtube' => 'YouTube',
                                                                'tiktok' => 'TikTok',
                                                                'linkedin' => 'LinkedIn',
                                                                'pinterest' => 'Pinterest',
                                                            ])
                                                            ->required()
                                                            ->live()
                                                            ->columnSpan(1)
                                                            ->afterStateUpdated(function ($state, callable $set) {
                                                                if ($state) {
                                                                    // Set base URL prefix based on platform
                                                                    $urlPrefix = match($state) {
                                                                        'instagram' => 'https://instagram.com/',
                                                                        'facebook' => 'https://facebook.com/',
                                                                        'twitter' => 'https://twitter.com/',
                                                                        'youtube' => 'https://youtube.com/',
                                                                        'tiktok' => 'https://tiktok.com/@',
                                                                        'linkedin' => 'https://linkedin.com/in/',
                                                                        'pinterest' => 'https://pinterest.com/',
                                                                        default => ''
                                                                    };
                                                                    $set('url_prefix', $urlPrefix);
                                                                }
                                                            }),

                                                        Forms\Components\TextInput::make('url')
                                                            ->label('URL or Username')
                                                            ->required()
                                                            ->prefix(fn (Forms\Get $get) => $get('url_prefix'))
                                                            ->columnSpan(2)
                                                            ->helperText(fn (Forms\Get $get) => 'Enter username only for ' . $get('platform')),
                                                    ])
                                                    ->columns(3),

                                                Forms\Components\Hidden::make('url_prefix'),
                                            ])
                                            ->itemLabel(fn (array $state): ?string =>
                                                $state['platform'] ? ucfirst($state['platform']) : null
                                            )
                                            ->visible(fn (Forms\Get $get) => $get('has_social_media'))
                                            ->defaultItems(0)
                                            ->reorderable()
                                            ->columnSpanFull(),
                                    ]),

                                // Opening Hours section hidden as requested
                                /* Forms\Components\Section::make('Opening Hours')
                                    ->schema([
                                        Forms\Components\Toggle::make('has_opening_hours')
                                            ->label('Add Opening Hours')
                                            ->helperText('Enable to add operating hours information')
                                            ->default(false)
                                            ->live(),

                                        Forms\Components\Repeater::make('opening_hours')
                                            ->schema([
                                                Forms\Components\Select::make('day')
                                                    ->options([
                                                        'monday' => 'Monday',
                                                        'tuesday' => 'Tuesday',
                                                        'wednesday' => 'Wednesday',
                                                        'thursday' => 'Thursday',
                                                        'friday' => 'Friday',
                                                        'saturday' => 'Saturday',
                                                        'sunday' => 'Sunday',
                                                    ])
                                                    ->required(),

                                                Forms\Components\TimePicker::make('open_time')
                                                    ->seconds(false)
                                                    ->required(),

                                                Forms\Components\TimePicker::make('close_time')
                                                    ->seconds(false)
                                                    ->required(),

                                                Forms\Components\Toggle::make('is_closed')
                                                    ->label('Closed')
                                                    ->default(false),
                                            ])
                                            ->columns(4)
                                            ->columnSpanFull()
                                            ->visible(fn (Forms\Get $get) => $get('has_opening_hours'))
                                            ->defaultItems(0)
                                    ), */
                            ]),

                        Forms\Components\Tabs\Tab::make('Media & Video')
                            ->schema([
                                Forms\Components\Section::make('Featured Image')
                                    ->schema([
                                        Forms\Components\FileUpload::make('featured_image')
                                            ->label('Featured Image')
                                            ->image()
                                            // ->imageEditor()
                                            // ->imageResizeMode('cover')
                                            // ->imageCropAspectRatio('16:9')
                                            ->directory('spotlights')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Video Information')
                                    ->schema([
                                        Forms\Components\Toggle::make('has_video')
                                            ->label('Has Video Tour')
                                            ->default(false)
                                            ->live(),

                                        Forms\Components\TextInput::make('video_url')
                                            ->label('Video URL')
                                            ->url()
                                            ->maxLength(255)
                                            ->visible(fn (Forms\Get $get) => $get('has_video')),

                                        Forms\Components\Select::make('video_provider')
                                            ->label('Video Provider')
                                            ->options([
                                                'youtube' => 'YouTube',
                                                'self' => 'Self Hosted',
                                            ])
                                            ->live()
                                            ->visible(fn (Forms\Get $get) => $get('has_video')),

                                        // Forms\Components\TextInput::make('video_id')
                                        //     ->label('Video ID')
                                        //     ->maxLength(100)
                                        //     ->helperText('Enter the YouTube video ID')
                                        //     ->visible(fn (Forms\Get $get) => $get('has_video') && $get('video_provider') === 'youtube'),

                                        Forms\Components\FileUpload::make('video_file')
                                            ->label('Video File')
                                            ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv'])
                                            ->maxSize(100 * 1024) // 100MB max size
                                            ->directory('spotlight-videos')
                                            ->helperText('Upload MP4, MOV, AVI, or WMV files (max 100MB)')
                                            ->visible(fn (Forms\Get $get) => $get('has_video') && $get('video_provider') === 'self'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Tags')
                            ->schema([
                                Forms\Components\Section::make('Tags')
                                    ->schema([
                                        Forms\Components\Select::make('tags')
                                            ->relationship('tags', 'name', function (Builder $query, callable $get) {
                                                $categoryId = $get('category_id');

                                                if ($categoryId) {
                                                    // Filter tags by the selected category
                                                    return $query->where('category_id', $categoryId);
                                                }

                                                // If no category is selected, show all tags
                                                return $query;
                                            })
                                            ->multiple()
                                            ->preload()
                                            ->searchable()
                                            ->helperText('Tags are filtered based on the selected category. New tags must be created in the Tags section.')
                                            ->live(),
                                    ]),
                            ]),

                        // We've moved Custom Attributes section directly under category selection
                    ])
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->circular(false)
                    ->square()
                    ->defaultImageUrl(fn () => asset('images/placeholder.jpg'))
                    ->label('Image'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_trending')
                    ->label('Trending')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->sortable()
                    ->numeric(2),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

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
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published')
                    ->placeholder('All Spotlights')
                    ->trueLabel('Published Only')
                    ->falseLabel('Unpublished Only')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured')
                    ->placeholder('All Spotlights')
                    ->trueLabel('Featured Only')
                    ->falseLabel('Non-Featured Only')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_trending')
                    ->label('Trending')
                    ->placeholder('All Spotlights')
                    ->trueLabel('Trending Only')
                    ->falseLabel('Non-Trending Only')
                    ->native(false),


                Tables\Filters\SelectFilter::make('tags')
                    ->relationship('tags', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publishSelected')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-globe-alt')
                        ->action(fn (Builder $query) => $query->update(['is_published' => true]))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('unpublishSelected')
                        ->label('Unpublish Selected')
                        ->icon('heroicon-o-globe-alt')
                        ->action(fn (Builder $query) => $query->update(['is_published' => false]))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('toggleFeatured')
                        ->label('Toggle Featured Status')
                        ->icon('heroicon-o-star')
                        ->action(fn (Builder $query) => $query->each(fn (Spotlight $spotlight) =>
                            $spotlight->update(['is_featured' => !$spotlight->is_featured])
                        )),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Removed AttributeValuesRelationManager as we now handle attributes directly in the form
            RelationManagers\MediaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpotlights::route('/'),
            'create' => Pages\CreateSpotlight::route('/create'),
            'edit' => Pages\EditSpotlight::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
