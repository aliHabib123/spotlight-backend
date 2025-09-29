<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShopResource\Pages;
use App\Filament\Resources\ShopResource\RelationManagers;
use App\Models\Shop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ShopResource extends Resource
{
    protected static ?string $model = Shop::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    
    protected static ?string $navigationGroup = 'Shop Management';
    
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
                            
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Categories')
                    ->schema([
                        Forms\Components\Select::make('categories')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique('shop_categories', 'slug'),
                            ])
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->image()
                            ->directory('shops/logos')
                            ->maxSize(1024),
                            
                        Forms\Components\FileUpload::make('cover_image')
                            ->image()
                            ->directory('shops/covers')
                            ->maxSize(2048),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Social Media')
                    ->schema([
                        Forms\Components\Repeater::make('social_links')
                            ->schema([
                                Forms\Components\Select::make('platform')
                                    ->label('Platform')
                                    ->options([
                                        'facebook' => 'Facebook',
                                        'instagram' => 'Instagram',
                                        'twitter' => 'Twitter/X',
                                        'linkedin' => 'LinkedIn',
                                        'youtube' => 'YouTube',
                                        'tiktok' => 'TikTok',
                                        'pinterest' => 'Pinterest',
                                        'snapchat' => 'Snapchat',
                                        'whatsapp' => 'WhatsApp',
                                        'telegram' => 'Telegram',
                                        'website' => 'Website',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('url')
                                    ->label('URL')
                                    ->url()
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => $state['platform'] ?? null)
                            ->addActionLabel('Add Social Link')
                            ->reorderableWithButtons()
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Display Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                            
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured'),
                            
                        Forms\Components\TextInput::make('display_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Shops with lower numbers will be displayed first'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->circular()
                    ->width(50)
                    ->height(50),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('categories.name')
                    ->badge()
                    ->color('primary'),
                // Custom HTML approach for social links with platform-specific colors
                Tables\Columns\TextColumn::make('social_links_html')
                    ->label('Social Media')
                    ->html()
                    ->getStateUsing(function ($record) {
                        $socialLinks = $record->social_links;
                        $html = '';
                        
                        // Define platform-specific colors with hex values
                        $platformColors = [
                            'facebook' => ['bg' => '#E7F0FF', 'text' => '#1877F2'], // Facebook blue
                            'instagram' => ['bg' => '#FFEAF0', 'text' => '#E4405F'], // Instagram pink
                            'twitter' => ['bg' => '#E6F7FF', 'text' => '#1DA1F2'], // Twitter blue
                            'linkedin' => ['bg' => '#E7F0FF', 'text' => '#0A66C2'], // LinkedIn blue
                            'youtube' => ['bg' => '#FFEBEE', 'text' => '#FF0000'], // YouTube red
                            'tiktok' => ['bg' => '#F0F0F0', 'text' => '#000000'], // TikTok black
                            'pinterest' => ['bg' => '#FFEBEE', 'text' => '#E60023'], // Pinterest red
                            'snapchat' => ['bg' => '#FFFDE7', 'text' => '#FFFC00'], // Snapchat yellow
                            'whatsapp' => ['bg' => '#E8F5E9', 'text' => '#25D366'], // WhatsApp green
                            'telegram' => ['bg' => '#E3F2FD', 'text' => '#0088CC'], // Telegram blue
                            'website' => ['bg' => '#F5F5F5', 'text' => '#666666'], // Website gray
                            // Add more platforms as needed
                        ];
                        
                        // Default color if platform not found
                        $defaultColor = ['bg' => '#E8F5E9', 'text' => '#4CAF50'];
                        
                        // Handle different data formats
                        if (is_array($socialLinks)) {
                            foreach ($socialLinks as $link) {
                                if (is_array($link) && isset($link['platform'])) {
                                    $platform = strtolower($link['platform']);
                                    $displayName = htmlspecialchars(ucfirst($platform));
                                    $colors = $platformColors[$platform] ?? $defaultColor;
                                    
                                    // Generate HTML with platform-specific hex colors
                                    $html .= "<span style='display:inline-flex;align-items:center;justify-content:center;min-height:1.5rem;padding:0.25rem 0.5rem;font-size:0.75rem;font-weight:500;line-height:1;border-radius:0.75rem;white-space:nowrap;color:{$colors['text']};background-color:{$colors['bg']};margin-right:0.25rem;margin-bottom:0.25rem;'>{$displayName}</span>";
                                }
                            }
                        }
                        
                        return !empty($html) ? $html : '-';
                    })
                    ->searchable(false)
                    ->sortable(false)
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('display_order')
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
                Tables\Filters\SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All Shops')
                    ->trueLabel('Active Shops')
                    ->falseLabel('Inactive Shops'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured')
                    ->placeholder('All Shops')
                    ->trueLabel('Featured Shops')
                    ->falseLabel('Non-Featured Shops'),
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
            ])
            ->defaultSort('display_order');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShops::route('/'),
            'create' => Pages\CreateShop::route('/create'),
            'edit' => Pages\EditShop::route('/{record}/edit'),
        ];
    }
}
