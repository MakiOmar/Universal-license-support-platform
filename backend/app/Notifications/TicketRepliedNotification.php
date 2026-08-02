<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\TicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketRepliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
        public TicketReply $reply,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New reply on ticket '.$this->ticket->ticket_number)
            ->line('There is a new reply on your support ticket.')
            ->line('Subject: '.$this->ticket->subject)
            ->line($this->reply->message)
            ->action(
                'View ticket',
                rtrim((string) config('app.frontend_url', config('app.url')), '/').'/app/tickets/'.$this->ticket->id,
            );
    }
}
