<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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

        $tickets = $query->orderByDesc('created_at')->paginate(25);

        return response()->json($tickets);
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['customer', 'license', 'product', 'replies']);

        return response()->json($ticket);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'license_id' => ['nullable', 'integer', 'exists:licenses,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['nullable', 'string', 'max:20'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);

        $ticketNumber = 'TKT-' . now()->format('Y') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

        $ticket = SupportTicket::create(array_merge($data, [
            'ticket_number' => $ticketNumber,
            'status' => 'open',
        ]));

        return response()->json($ticket, 201);
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'subject' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'priority' => ['sometimes', 'string', 'max:20'],
            'status' => ['sometimes', 'string', 'max:20'],
            'category' => ['sometimes', 'string', 'max:50'],
            'assigned_to' => ['nullable', 'integer'],
        ]);

        $ticket->update($data);

        return response()->json($ticket);
    }

    public function close(SupportTicket $ticket)
    {
        $ticket->status = 'closed';
        $ticket->resolved_at = now();
        $ticket->save();

        return response()->json($ticket);
    }

    public function addReply(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'user_type' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $reply = TicketReply::create(array_merge($data, [
            'ticket_id' => $ticket->id,
        ]));

        return response()->json($reply, 201);
    }

    public function listReplies(SupportTicket $ticket)
    {
        $replies = $ticket->replies()->orderBy('created_at')->get();

        return response()->json($replies);
    }
}


