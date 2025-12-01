<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Resources\Api\V1\SupportTicketResource;
use App\Http\Resources\Api\V1\TicketReplyResource;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with(['customer', 'license', 'product']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->get('customer_id'));
        }

        $tickets = $query->orderByDesc('created_at')->paginate(25);

        return SupportTicketResource::collection($tickets);
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['customer', 'license', 'product', 'replies']);

        return new SupportTicketResource($ticket);
    }

    public function store(StoreTicketRequest $request)
    {
        $data = $request->validated();

        // Generate unique ticket number
        do {
            $ticketNumber = 'TKT-' . now()->format('Y') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (SupportTicket::where('ticket_number', $ticketNumber)->exists());

        $ticket = SupportTicket::create(array_merge($data, [
            'ticket_number' => $ticketNumber,
            'status' => 'open',
            'priority' => $data['priority'] ?? 'medium',
        ]));

        $ticket->load(['customer', 'license', 'product']);

        return new SupportTicketResource($ticket);
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'subject' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:5000'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'status' => ['sometimes', 'string', 'in:open,in_progress,waiting_customer,resolved,closed'],
            'category' => ['sometimes', 'string', 'in:technical,billing,feature_request,bug_report,account,license'],
            'assigned_to' => ['nullable', 'integer'],
        ]);

        $ticket->update($data);
        $ticket->load(['customer', 'license', 'product', 'replies']);

        return new SupportTicketResource($ticket);
    }

    public function close(SupportTicket $ticket)
    {
        $ticket->status = 'closed';
        $ticket->resolved_at = now();
        $ticket->save();
        $ticket->load(['customer', 'license', 'product', 'replies']);

        return new SupportTicketResource($ticket);
    }

    public function addReply(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'user_type' => ['required', 'string', 'in:customer,agent,system'],
            'message' => ['required', 'string', 'max:5000'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $reply = TicketReply::create(array_merge($data, [
            'ticket_id' => $ticket->id,
            'is_internal' => $data['is_internal'] ?? false,
        ]));

        return new TicketReplyResource($reply);
    }

    public function listReplies(SupportTicket $ticket)
    {
        $replies = $ticket->replies()->orderBy('created_at')->get();

        return TicketReplyResource::collection($replies);
    }
}


