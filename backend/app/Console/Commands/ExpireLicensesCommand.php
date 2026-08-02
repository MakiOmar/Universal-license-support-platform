<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Notifications\LicenseExpiredNotification;
use Illuminate\Console\Command;

class ExpireLicensesCommand extends Command
{
    protected $signature = 'licenses:expire';

    protected $description = 'Mark expired licenses and notify customers';

    public function handle(): int
    {
        $licenses = License::query()
            ->with(['customer', 'product'])
            ->where('status', License::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($licenses as $license) {
            $license->update(['status' => License::STATUS_EXPIRED]);

            if ($license->customer) {
                $license->customer->notify(new LicenseExpiredNotification($license));
            }
        }

        $this->info('Expired '.$licenses->count().' license(s).');

        return self::SUCCESS;
    }
}
