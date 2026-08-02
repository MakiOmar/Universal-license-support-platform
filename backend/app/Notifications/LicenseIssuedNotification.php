<?php

namespace App\Notifications;

use App\Models\License;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseIssuedNotification extends Notification implements ShouldQueue
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

        $mail = (new MailMessage)
            ->subject('Your license for '.($this->license->product?->name ?? 'ULSP'))
            ->line('Your license has been issued.')
            ->line('Product: '.($this->license->product?->name ?? 'N/A'))
            ->line('License key: '.$this->license->license_key)
            ->line('Max activations: '.$this->license->max_activations);

        if ($this->license->expires_at) {
            $mail->line('Expires at: '.$this->license->expires_at->toDayDateTimeString());
        } else {
            $mail->line('This license does not expire.');
        }

        return $mail->action('View licenses', rtrim((string) config('app.frontend_url', config('app.url')), '/').'/app/licenses');
    }
}
