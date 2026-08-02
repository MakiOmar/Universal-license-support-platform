<?php

namespace App\Notifications;

use App\Models\License;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public License $license,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->license->loadMissing('product');

        return (new MailMessage)
            ->subject('License expiring soon: '.($this->license->product?->name ?? 'ULSP'))
            ->line('Your license will expire soon.')
            ->line('License key: '.$this->license->license_key)
            ->line('Expires at: '.($this->license->expires_at?->toDayDateTimeString() ?? 'N/A'))
            ->action('Renew / manage', rtrim((string) config('app.frontend_url', config('app.url')), '/').'/app/licenses');
    }
}
