<?php

namespace App\Filament\Resources\PricingTiers;

use App\Filament\Concerns\ChecksAdminRole;
use App\Filament\Resources\PricingTiers\Pages\ManagePricingTiers;
use App\Models\PricingTier;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PricingTierResource extends Resource
{
    use ChecksAdminRole;

    protected static ?string $model = PricingTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

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
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('currency')
                    ->required()
                    ->maxLength(3)
                    ->default('USD'),
                TextInput::make('max_activations')
                    ->required()
                    ->numeric()
                    ->default(1),
                Select::make('billing_cycle')
                    ->required()
                    ->options(PricingTier::billingCycleOptions())
                    ->default(PricingTier::BILLING_YEARLY)
                    ->helperText('One-time and lifetime are single payments (no subscription). License does not expire.'),
                TextInput::make('stripe_price_id')
                    ->maxLength(255)
                    ->helperText('Optional. For recurring tiers use a Stripe recurring Price ID; for one-time use a one-time Price ID.'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('price')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),
                TextColumn::make('max_activations'),
                TextColumn::make('billing_cycle')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => PricingTier::billingCycleOptions()[$state] ?? (string) $state),
                IconColumn::make('is_active')
                    ->boolean(),
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
            'index' => ManagePricingTiers::route('/'),
        ];
    }
}
