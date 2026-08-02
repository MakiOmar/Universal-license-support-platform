<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Concerns\ChecksAdminRole;
use App\Filament\Resources\Payments\Pages\ManagePayments;
use App\Models\Payment;
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

class PaymentResource extends Resource
{
    use ChecksAdminRole;

    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

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
                Select::make('customer_id')
                    ->relationship('customer', 'email')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('license_id')
                    ->relationship('license', 'license_key')
                    ->searchable()
                    ->preload(),
                Select::make('pricing_tier_id')
                    ->relationship('pricingTier', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->maxLength(3)
                    ->default('USD'),
                TextInput::make('gateway')
                    ->default('stripe'),
                TextInput::make('gateway_reference'),
                Select::make('status')
                    ->options([
                        Payment::STATUS_PENDING => 'Pending',
                        Payment::STATUS_COMPLETED => 'Completed',
                        Payment::STATUS_FAILED => 'Failed',
                        Payment::STATUS_REFUNDED => 'Refunded',
                    ])
                    ->required(),
                DateTimePicker::make('paid_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.email')
                    ->searchable(),
                TextColumn::make('amount')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),
                TextColumn::make('gateway'),
                TextColumn::make('gateway_reference')
                    ->limit(20),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('paid_at')
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
            'index' => ManagePayments::route('/'),
        ];
    }
}
