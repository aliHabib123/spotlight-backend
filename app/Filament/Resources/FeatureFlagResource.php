<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeatureFlagResource\Pages;
use App\Models\FeatureFlag;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class FeatureFlagResource extends Resource
{
    protected static ?string $model = FeatureFlag::class;



    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        /** @var User $user */
        $user = Auth::user();

        // Check if the user has the super-admin role
        return $user->hasRole('super-admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('key')
                    ->required()
                    ->maxLength(255)
                    ->unique(FeatureFlag::class, 'key', ignoreRecord: true)
                    ->rules(['alpha_dash'])
                    ->helperText('This is used as a reference in the code.'),

                Forms\Components\Toggle::make('is_enabled')
                    ->label('Enabled')
                    ->default(false)
                    ->helperText('Feature is active when enabled.'),

                Forms\Components\Textarea::make('description')
                    ->maxLength(1000)
                    ->columnSpanFull(),

                Forms\Components\KeyValue::make('config')
                    ->keyLabel('Setting')
                    ->valueLabel('Value')
                    ->helperText('Additional configuration for this feature flag.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Status')
                    ->placeholder('All Flags')
                    ->trueLabel('Enabled')
                    ->falseLabel('Disabled')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('toggle')
                    ->label(fn ($record) => $record->is_enabled ? 'Disable' : 'Enable')
                    ->icon(fn ($record) => $record->is_enabled ? 'heroicon-o-x-mark' : 'heroicon-o-check')
                    ->color(fn ($record) => $record->is_enabled ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['is_enabled' => !$record->is_enabled])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('enableSelected')
                        ->label('Enable Selected')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['is_enabled' => true])),
                    Tables\Actions\BulkAction::make('disableSelected')
                        ->label('Disable Selected')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each->update(['is_enabled' => false])),
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
            'index' => Pages\ListFeatureFlags::route('/'),
            'create' => Pages\CreateFeatureFlag::route('/create'),
            'edit' => Pages\EditFeatureFlag::route('/{record}/edit'),
        ];
    }
}
