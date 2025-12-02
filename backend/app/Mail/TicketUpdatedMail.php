<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public SupportTicket $ticket,
        public string $updateType = 'updated'
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->updateType) {
            'reply' => 'New Reply on Ticket - ' . $this->ticket->ticket_number,
            'closed' => 'Ticket Closed - ' . $this->ticket->ticket_number,
            default => 'Ticket Updated - ' . $this->ticket->ticket_number,
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-updated',
            with: [
                'ticket' => $this->ticket,
                'customer' => $this->ticket->customer,
                'updateType' => $this->updateType,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
