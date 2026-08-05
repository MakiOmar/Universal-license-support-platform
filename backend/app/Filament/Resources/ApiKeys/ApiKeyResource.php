<?php

namespace App\Filament\Resources\ApiKeys;

use App\Filament\Concerns\ChecksAdminRole;
use App\Filament\Resources\ApiKeys\Pages\ManageApiKeys;
use App\Models\ApiKey;
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
use Illuminate\Support\Str;

class ApiKeyResource extends Resource
{
    use ChecksAdminRole;

    protected static ?string $model = ApiKey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        return static::isFullAdmin();
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
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('customer_id')
                    ->relationship('customer', 'email')
                    ->searchable()
                    ->preload(),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(fn (callable $get): bool => (int) ($get('trial_days') ?? 0) > 0)
                    ->helperText('Required when trial days is greater than zero.'),
                TextInput::make('key')
                    ->default(fn () => 'ulsp_'.Str::random(32))
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('secret_hash')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                TextInput::make('rate_limit')
                    ->numeric()
                    ->default(1000),
                TextInput::make('trial_days')
                    ->label('Trial days')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required()
                    ->live()
                    ->helperText('Set greater than 0 to enable POST /licenses/start-trial for this app. Product is required.'),
                Select::make('status')
                    ->options([
                        ApiKey::STATUS_ACTIVE => 'Active',
                        ApiKey::STATUS_REVOKED => 'Revoked',
                    ])
                    ->default('active'),
                DateTimePicker::make('expires_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('key')
                    ->copyable()
                    ->limit(20),
                TextColumn::make('customer.email'),
                TextColumn::make('product.name'),
                TextColumn::make('trial_days')
                    ->label('Trial days')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('last_used_at')
                    ->dateTime(),
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
            'index' => ManageApiKeys::route('/'),
        ];
    }
}
