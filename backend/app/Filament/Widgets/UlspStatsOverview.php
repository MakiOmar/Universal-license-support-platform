<?php

namespace App\Filament\Widgets;

use App\Models\License;
use App\Models\Payment;
use App\Models\SupportTicket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UlspStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $openTickets = SupportTicket::whereIn('status', [
            SupportTicket::STATUS_OPEN,
            SupportTicket::STATUS_IN_PROGRESS,
            SupportTicket::STATUS_WAITING_CUSTOMER,
        ])->count();

        $monthRevenue = Payment::query()
            ->where('status', Payment::STATUS_COMPLETED)
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount');

        return [
            Stat::make('Total Licenses', License::count())
                ->description(License::where('status', License::STATUS_ACTIVE)->count().' active')
                ->descriptionIcon('heroicon-m-key')
                ->color('success'),
            Stat::make('Open Tickets', $openTickets)
                ->description(SupportTicket::where('priority', 'urgent')->where('status', '!=', SupportTicket::STATUS_CLOSED)->count().' urgent')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('warning'),
            Stat::make('Month Revenue', '$'.number_format((float) $monthRevenue, 2))
                ->description('Completed payments this month')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),
        ];
    }
}
