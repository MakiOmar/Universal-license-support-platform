<?php

namespace App\Console\Commands;

use App\Models\License;
use Illuminate\Console\Command;

class ExpireLicensesCommand extends Command
{
    protected $signature = 'licenses:expire';

    protected $description = 'Mark expired licenses and update their status';

    public function handle(): int
    {
        $count = License::query()
            ->where('status', License::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => License::STATUS_EXPIRED]);

        $this->info("Expired {$count} license(s).");

        return self::SUCCESS;
    }
}
