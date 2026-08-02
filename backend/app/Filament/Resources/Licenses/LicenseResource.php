<?php

namespace App\Filament\Resources\Licenses;

use App\Filament\Resources\Licenses\Pages\ManageLicenses;
use App\Models\License;
use App\Models\PricingTier;
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

class LicenseResource extends Resource
{
    protected static ?string $model = License::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $recordTitleAttribute = 'license_key';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Auto-generated on create; shown read-only when editing.
                TextInput::make('license_key')
                    ->label('License key')
                    ->disabled()
                    ->dehydrated(false)
                    ->hiddenOn('create')
                    ->helperText('Generated automatically from the product key prefix.'),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('pricing_tier_id', null))
                    ->helperText('License key uses this product\'s key prefix.'),
                Select::make('customer_id')
                    ->relationship('customer', 'email')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('pricing_tier_id')
                    ->label('Pricing tier')
                    ->options(function (callable $get): array {
                        $productId = $get('product_id');

                        if (! $productId) {
                            return [];
                        }

                        return PricingTier::query()
                            ->where('product_id', $productId)
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->preload(),
                TextInput::make('max_activations')
                    ->required()
                    ->numeric()
                    ->default(1),
                Select::make('status')
                    ->options([
                        License::STATUS_PENDING => 'Pending',
                        License::STATUS_ACTIVE => 'Active',
                        License::STATUS_EXPIRED => 'Expired',
                        License::STATUS_SUSPENDED => 'Suspended',
                        License::STATUS_CANCELLED => 'Cancelled',
                    ])
                    ->required()
                    ->default(License::STATUS_ACTIVE),
                DateTimePicker::make('purchased_at')
                    ->default(now()),
                DateTimePicker::make('expires_at')
                    ->helperText('Leave empty to use the pricing tier billing cycle (default: 1 year).'),
                DateTimePicker::make('support_expires_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license_key')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('product.name')
                    ->sortable(),
                TextColumn::make('product.key_prefix')
                    ->label('Prefix')
                    ->toggleable(),
                TextColumn::make('customer.email')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('max_activations'),
                TextColumn::make('expires_at')
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
            'index' => ManageLicenses::route('/'),
        ];
    }
}
