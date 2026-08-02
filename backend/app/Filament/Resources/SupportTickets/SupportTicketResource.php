<?php

namespace App\Filament\Resources\SupportTickets;

use App\Filament\Concerns\ChecksAdminRole;
use App\Filament\Resources\SupportTickets\Pages\ManageSupportTickets;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\TicketService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SupportTicketResource extends Resource
{
    use ChecksAdminRole;

    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $recordTitleAttribute = 'ticket_number';

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
        return static::canAccessPanelRoles();
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
                TextInput::make('ticket_number')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Select::make('customer_id')
                    ->relationship('customer', 'email')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('license_id')
                    ->relationship('license', 'license_key')
                    ->searchable()
                    ->preload(),
                TextInput::make('subject')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ])
                    ->default('medium'),
                Select::make('status')
                    ->options([
                        SupportTicket::STATUS_OPEN => 'Open',
                        SupportTicket::STATUS_IN_PROGRESS => 'In Progress',
                        SupportTicket::STATUS_WAITING_CUSTOMER => 'Waiting Customer',
                        SupportTicket::STATUS_RESOLVED => 'Resolved',
                        SupportTicket::STATUS_CLOSED => 'Closed',
                    ])
                    ->default('open'),
                Select::make('category')
                    ->options([
                        'technical' => 'Technical',
                        'billing' => 'Billing',
                        'feature_request' => 'Feature Request',
                        'bug_report' => 'Bug Report',
                        'account' => 'Account',
                        'license' => 'License',
                    ]),
                Select::make('assigned_to')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),
                Textarea::make('replies_preview')
                    ->label('Conversation')
                    ->disabled()
                    ->dehydrated(false)
                    ->rows(8)
                    ->columnSpanFull()
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticket_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('customer.email')
                    ->searchable(),
                TextColumn::make('priority')
                    ->badge(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('assignee.name')
                    ->label('Assigned'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('reply')
                    ->label('Reply')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->form([
                        Textarea::make('message')
                            ->required()
                            ->rows(4),
                        Toggle::make('is_internal')
                            ->label('Internal note')
                            ->helperText('Internal notes are hidden from the customer.'),
                        FileUpload::make('attachments')
                            ->multiple()
                            ->maxFiles(5)
                            ->disk('local')
                            ->directory('ticket-attachments-tmp')
                            ->storeFiles(false),
                    ])
                    ->action(function (SupportTicket $record, array $data, TicketService $ticketService): void {
                        /** @var User $user */
                        $user = auth()->user();
                        $files = [];
                        foreach ($data['attachments'] ?? [] as $upload) {
                            if ($upload) {
                                $files[] = $upload;
                            }
                        }

                        $ticketService->reply(
                            $record,
                            $user,
                            $data['message'],
                            (bool) ($data['is_internal'] ?? false),
                            $files,
                        );

                        Notification::make()
                            ->success()
                            ->title('Reply posted')
                            ->send();
                    }),
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, SupportTicket $record): array {
                        $replies = $record->replies()->orderBy('created_at')->get()
                            ->map(fn ($reply) => ($reply->is_internal ? '[internal] ' : '').$reply->message)
                            ->implode("\n---\n");
                        $data['replies_preview'] = $replies ?: 'No replies yet.';

                        return $data;
                    }),
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
            'index' => ManageSupportTickets::route('/'),
        ];
    }
}
