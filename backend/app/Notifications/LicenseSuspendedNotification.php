<?php

namespace App\Notifications;

use App\Models\License;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseSuspendedNotification extends Notification implements ShouldQueue
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
            ->subject('License suspended: '.($this->license->product?->name ?? 'ULSP'))
            ->line('Your license has been suspended.')
            ->line('License key: '.$this->license->license_key)
            ->line('If you believe this is a mistake, contact support.');
    }
}
