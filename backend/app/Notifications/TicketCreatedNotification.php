<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New support ticket: '.$this->ticket->ticket_number)
            ->line('A new support ticket has been created.')
            ->line('Subject: '.$this->ticket->subject)
            ->line('Ticket #: '.$this->ticket->ticket_number)
            ->line('Customer: '.$this->ticket->customer?->email);
    }
}
