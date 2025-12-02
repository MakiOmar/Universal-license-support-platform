<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Mail\LicenseExpiringMail;
use App\Models\License;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiringLicenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licenses:check-expiring 
                            {--days=30 : Number of days before expiration to send notification}
                            {--dry-run : Run without sending emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for licenses expiring soon and send email notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Checking for licenses expiring in {$days} days...");

        // Find licenses expiring within the specified days
        $expirationDate = now()->addDays($days);
        $expirationStart = now()->addDays($days - 1)->startOfDay();
        $expirationEnd = $expirationDate->endOfDay();

        $licenses = License::with(['customer', 'product'])
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$expirationStart, $expirationEnd])
            ->whereDoesntHave('customer', function ($query) {
                $query->where('status', '!=', 'active');
            })
            ->get();

        $this->info("Found {$licenses->count()} license(s) expiring soon.");

        $sent = 0;
        $skipped = 0;

        foreach ($licenses as $license) {
            // Check if we've already sent a notification for this expiration window
            $notificationKey = "license_expiring_notification_{$license->id}_{$days}days";
            
            if (cache()->has($notificationKey)) {
                $this->warn("Skipping license {$license->id} - notification already sent");
                $skipped++;
                continue;
            }

            if (!$license->customer || !$license->customer->email) {
                $this->warn("Skipping license {$license->id} - no customer email");
                $skipped++;
                continue;
            }

            $daysUntilExpiration = now()->diffInDays($license->expires_at, false);

            if ($dryRun) {
                $this->line("Would send notification for license {$license->id} (expires in {$daysUntilExpiration} days)");
            } else {
                try {
                    SendEmailJob::dispatch(
                        new LicenseExpiringMail($license, $daysUntilExpiration),
                        $license->customer->email
                    );

                    // Mark notification as sent (expires when license expires)
                    cache()->put(
                        $notificationKey,
                        true,
                        $license->expires_at->diffInSeconds(now())
                    );

                    $this->info("Sent notification for license {$license->id} to {$license->customer->email}");
                    $sent++;
                } catch (\Exception $e) {
                    $this->error("Failed to send notification for license {$license->id}: {$e->getMessage()}");
                    Log::error("Failed to send license expiring notification", [
                        'license_id' => $license->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info("Completed: {$sent} sent, {$skipped} skipped");

        return Command::SUCCESS;
    }
}

