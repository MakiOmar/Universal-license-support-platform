<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyCustomerEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $code,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your email')
            ->line('Use this verification code to confirm your email address:')
            ->line('**'.$this->code.'**')
            ->line('This code expires in 60 minutes.');
    }
}
