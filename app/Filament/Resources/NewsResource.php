<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsResource\Pages;
use App\Filament\Resources\NewsResource\RelationManagers;
use App\Models\News;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    
    protected static ?string $navigationGroup = 'News Management';
    
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
                                $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),
                        
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                            
                        Forms\Components\Select::make('news_category_id')
                            ->relationship('category', 'name')
                            ->required()
                            ->preload()
                            ->searchable(),
                            
                        Forms\Components\Select::make('user_id')
                            ->relationship('author', 'name')
                            ->required()
                            ->default(fn () => Auth::check() ? Auth::id() : null)
                            ->preload()
                            ->searchable(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Content')
                    ->schema([
                        Forms\Components\Textarea::make('summary')
                            ->maxLength(500)
                            ->columnSpanFull(),
                            
                        Forms\Components\RichEditor::make('content')
                            ->required()
                            ->columnSpanFull(),
                            
                        Forms\Components\FileUpload::make('featured_image')
                            ->image()
                            ->directory('news')
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Publication')
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->label('Published')
                            ->default(false),
                            
                        Forms\Components\Toggle::make('show_date')
                            ->label('Show Date')
                            ->default(true),
                            
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publish Date')
                            ->default(now())
                            ->visible(fn (Forms\Get $get) => $get('is_published')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                    
                Tables\Columns\TextColumn::make('category.name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('author.name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\ImageColumn::make('featured_image')
                    ->circular(),
                    
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
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
                Tables\Filters\SelectFilter::make('news_category_id')
                    ->relationship('category', 'name')
                    ->label('Category')
                    ->preload()
                    ->searchable(),
                    
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('author', 'name')
                    ->label('Author')
                    ->preload()
                    ->searchable(),
                    
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published Status')
                    ->placeholder('All News')
                    ->trueLabel('Published Only')
                    ->falseLabel('Drafts Only'),
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
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
        ];
    }
}
