<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Events';
    
    protected static ?int $navigationSort = 2;
    
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
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                                $operation === 'create' ? $set('slug', Str::slug($state)) : null
                            ),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Event::class, 'slug', ignoreRecord: true)
                            ->rules(['alpha_dash']),
                        Forms\Components\Select::make('event_category_id')
                            ->relationship('eventCategory', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->columnSpanFull()
                            ->maxLength(5000),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Location & Contact')
                    ->schema([
                        Forms\Components\Select::make('event_location_id')
                            ->relationship('eventLocation', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Select from existing locations. New locations must be created in the Event Locations section.'),
                        Forms\Components\TextInput::make('phone_number')
                            ->tel()
                            ->maxLength(20)
                            ->helperText('Contact phone number for this event'),
                        Forms\Components\TextInput::make('map_url')
                            ->url()
                            ->maxLength(500)
                            ->helperText('Google Maps URL or similar')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('booking_link')
                            ->url()
                            ->maxLength(500)
                            ->helperText('External booking/registration URL')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image')
                            ->image()
                            ->directory('events')
                            ->columnSpanFull(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Event Schedules')
                    ->schema([
                        Forms\Components\Repeater::make('schedules')
                            ->relationship()
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->required()
                                    ->native(false),
                                Forms\Components\TimePicker::make('start_time')
                                    ->required()
                                    ->seconds(false),
                                Forms\Components\TimePicker::make('end_time')
                                    ->required()
                                    ->seconds(false),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->reorderable()
                            ->itemLabel(fn (array $state): ?string => 
                                $state['date'] ? date('M d, Y', strtotime($state['date'])) . ' - ' . 
                                ($state['start_time'] ?? '') . ' to ' . ($state['end_time'] ?? '') : null
                            )
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Status & Features')
                    ->schema([
                        Forms\Components\Toggle::make('is_featured')
                            ->default(false)
                            ->helperText('Featured events appear prominently'),
                        Forms\Components\Toggle::make('is_published')
                            ->default(true)
                            ->helperText('Only published events are visible to users'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->circular(false)
                    ->square()
                    ->defaultImageUrl(fn () => asset('images/placeholder.jpg'))
                    ->label('Image'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('eventCategory.name')
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('eventLocation.name')
                    ->label('Location')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('schedules_count')
                    ->counts('schedules')
                    ->label('Schedules')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
