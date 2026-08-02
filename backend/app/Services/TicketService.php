<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class TicketService
{
    public function create(Customer $customer, array $data): SupportTicket
    {
        return DB::transaction(function () use ($customer, $data) {
            $ticket = SupportTicket::create([
                'ticket_number' => $this->generateTicketNumber(),
                'customer_id' => $customer->id,
                'license_id' => $data['license_id'] ?? null,
                'product_id' => $data['product_id'] ?? null,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'priority' => $data['priority'] ?? 'medium',
                'status' => SupportTicket::STATUS_OPEN,
                'category' => $data['category'] ?? null,
            ]);

            TicketReply::create([
                'ticket_id' => $ticket->id,
                'author_type' => Customer::class,
                'author_id' => $customer->id,
                'message' => $data['description'],
                'is_internal' => false,
            ]);

            Notification::route('mail', config('mail.from.address'))
                ->notify(new TicketCreatedNotification($ticket));

            return $ticket->load(['customer', 'product', 'license']);
        });
    }

    public function reply(
        SupportTicket $ticket,
        Customer|User $author,
        string $message,
        bool $isInternal = false,
    ): TicketReply {
        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'author_type' => $author::class,
            'author_id' => $author->id,
            'message' => $message,
            'is_internal' => $isInternal,
        ]);

        if ($author instanceof User && $ticket->first_responded_at === null) {
            $ticket->update(['first_responded_at' => now()]);
        }

        if ($ticket->status === SupportTicket::STATUS_OPEN && $author instanceof User) {
            $ticket->update(['status' => SupportTicket::STATUS_IN_PROGRESS]);
        }

        return $reply;
    }

    public function assign(SupportTicket $ticket, User $agent): SupportTicket
    {
        $ticket->update([
            'assigned_to' => $agent->id,
            'status' => SupportTicket::STATUS_IN_PROGRESS,
        ]);

        return $ticket->fresh();
    }

    public function close(SupportTicket $ticket): SupportTicket
    {
        $ticket->update([
            'status' => SupportTicket::STATUS_CLOSED,
            'resolved_at' => now(),
        ]);

        return $ticket->fresh();
    }

    protected function generateTicketNumber(): string
    {
        do {
            $number = 'TKT-'.strtoupper(Str::random(8));
        } while (SupportTicket::where('ticket_number', $number)->exists());

        return $number;
    }
}
