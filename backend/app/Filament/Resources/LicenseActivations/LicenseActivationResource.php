<?php

namespace App\Filament\Resources\LicenseActivations;

use App\Filament\Concerns\ChecksAdminRole;
use App\Filament\Resources\LicenseActivations\Pages\ManageLicenseActivations;
use App\Models\LicenseActivation;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LicenseActivationResource extends Resource
{
    use ChecksAdminRole;

    protected static ?string $model = LicenseActivation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    public static function canViewAny(): bool
    {
        return static::canAccessPanelRoles();
    }

    public static function canCreate(): bool
    {
        return static::isFullAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return static::isFullAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return static::isFullAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return static::isFullAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('license_id')
                    ->relationship('license', 'license_key')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('activation_type')
                    ->required()
                    ->maxLength(50),
                TextInput::make('activation_value')
                    ->required()
                    ->maxLength(255),
                TextInput::make('device_name')
                    ->label('Device / phone model')
                    ->maxLength(120)
                    ->helperText('e.g. Honor 6xb'),
                TextInput::make('platform')
                    ->maxLength(50)
                    ->helperText('e.g. android, ios'),
                TextInput::make('app_version')
                    ->maxLength(50),
                TextInput::make('activation_hash')
                    ->required()
                    ->maxLength(64),
                TextInput::make('ip_address')
                    ->maxLength(45),
                Select::make('status')
                    ->options([
                        LicenseActivation::STATUS_ACTIVE => 'Active',
                        LicenseActivation::STATUS_DEACTIVATED => 'Deactivated',
                    ])
                    ->required(),
                DateTimePicker::make('activated_at'),
                DateTimePicker::make('last_check_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license.license_key')
                    ->searchable(),
                TextColumn::make('device_name')
                    ->label('Device')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('platform')
                    ->toggleable(),
                TextColumn::make('app_version')
                    ->label('App')
                    ->toggleable(),
                TextColumn::make('activation_type')
                    ->badge(),
                TextColumn::make('activation_value')
                    ->limit(30),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('ip_address')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('activated_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_check_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLicenseActivations::route('/'),
        ];
    }
}
