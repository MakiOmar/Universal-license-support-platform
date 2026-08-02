<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Notifications\LicenseExpiringNotification;
use Illuminate\Console\Command;

class NotifyExpiringLicensesCommand extends Command
{
    protected $signature = 'licenses:notify-expiring {--days=7}';

    protected $description = 'Notify customers whose licenses expire within N days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $from = now();
        $to = now()->addDays($days);

        $licenses = License::query()
            ->with(['customer', 'product'])
            ->where('status', License::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$from, $to])
            ->get();

        foreach ($licenses as $license) {
            if ($license->customer) {
                $license->customer->notify(new LicenseExpiringNotification($license));
            }
        }

        $this->info('Notified '.$licenses->count().' expiring license(s).');

        return self::SUCCESS;
    }
}
