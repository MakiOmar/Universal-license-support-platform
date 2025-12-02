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
        $perPage = min($request->get('per_page', 25), 100);

        $query = SupportTicket::with([
            'customer:id,email,first_name,last_name',
            'license:id,license_key,product_id',
            'product:id,name,slug',
            'assignedAdmin:id,name,email',
        ])->select('id', 'ticket_number', 'customer_id', 'license_id', 'product_id', 'subject', 'priority', 'status', 'category', 'assigned_to', 'created_at', 'updated_at');

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

        $tickets = $query->orderByDesc('created_at')->paginate($perPage);

        return SupportTicketResource::collection($tickets);
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['customer', 'license', 'product', 'replies.attachments', 'assignedAdmin']);

        return new SupportTicketResource($ticket);
    }

    public function store(StoreTicketRequest $request)
    {
        $data = $request->validated();

        // If customer is authenticated via customer.auth middleware, use their ID
        // and ensure they can only create tickets for themselves
        if ($request->has('customer')) {
            $customer = $request->user();
            $data['customer_id'] = $customer->id;
            
            // Security: Ensure customer can only link their own licenses
            if (isset($data['license_id'])) {
                $license = \App\Models\License::find($data['license_id']);
                if (!$license || $license->customer_id !== $customer->id) {
                    return response()->json([
                        'message' => 'Unauthorized. You can only create tickets for your own licenses.',
                    ], 403);
                }
            }
        }

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

        // Send email notification to customer via queue
        if ($ticket->customer && $ticket->customer->email) {
            \App\Jobs\SendEmailJob::dispatch(
                new \App\Mail\TicketCreatedMail($ticket),
                $ticket->customer->email
            );
        }

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
        $ticket->load(['customer', 'license', 'product', 'replies', 'assignedAdmin']);

        // Send email notification if ticket was assigned
        if (isset($data['assigned_to']) && $ticket->customer && $ticket->customer->email) {
            \App\Jobs\SendEmailJob::dispatch(
                new \App\Mail\TicketUpdatedMail($ticket, 'assigned'),
                $ticket->customer->email
            );
        }

        return new SupportTicketResource($ticket);
    }

    public function close(SupportTicket $ticket)
    {
        $ticket->status = 'closed';
        $ticket->resolved_at = now();
        $ticket->save();
        $ticket->load(['customer', 'license', 'product', 'replies']);

        // Send email notification to customer via queue
        if ($ticket->customer && $ticket->customer->email) {
            \App\Jobs\SendEmailJob::dispatch(
                new \App\Mail\TicketUpdatedMail($ticket, 'closed'),
                $ticket->customer->email
            );
        }

        return new SupportTicketResource($ticket);
    }

    public function addReply(Request $request, SupportTicket $ticket)
    {
        // If customer is authenticated, use their info automatically
        if ($request->has('customer')) {
            $customer = $request->user();
            $data = $request->validate([
                'message' => ['required', 'string', 'max:5000'],
                'is_internal' => ['nullable', 'boolean'],
            ]);
            
            // Ensure customer can only reply to their own tickets
            if ($ticket->customer_id !== $customer->id) {
                return response()->json([
                    'message' => 'Unauthorized. You can only reply to your own tickets.',
                ], 403);
            }
            
            $data['user_id'] = $customer->id;
            $data['user_type'] = 'customer';
            $data['is_internal'] = false; // Customers cannot create internal notes
        } else {
            // Admin/API key authentication
            $data = $request->validate([
                'user_id' => ['required', 'integer'],
                'user_type' => ['required', 'string', 'in:customer,agent,system'],
                'message' => ['required', 'string', 'max:5000'],
                'is_internal' => ['nullable', 'boolean'],
            ]);
        }

        $reply = TicketReply::create(array_merge($data, [
            'ticket_id' => $ticket->id,
            'is_internal' => $data['is_internal'] ?? false,
        ]));

        // Load attachments relationship
        $reply->load('attachments');

        // Refresh ticket to get latest data
        $ticket->refresh();
        $ticket->load(['customer', 'replies']);

        // Send email notification to customer if reply is not internal
        if (!$reply->is_internal && $ticket->customer && $ticket->customer->email) {
            $updateType = $data['user_type'] === 'customer' ? 'reply' : 'updated';
            \App\Jobs\SendEmailJob::dispatch(
                new \App\Mail\TicketUpdatedMail($ticket, $updateType),
                $ticket->customer->email
            );
        }

        return new TicketReplyResource($reply);
    }

    public function listReplies(SupportTicket $ticket)
    {
        $replies = $ticket->replies()->orderBy('created_at')->get();

        return TicketReplyResource::collection($replies);
    }

    public function uploadAttachment(Request $request, SupportTicket $ticket)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,log'],
            'reply_id' => ['nullable', 'integer', 'exists:ticket_replies,id'],
        ]);

        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('ticket-attachments', $filename, 'public');

        $attachment = \App\Models\TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'reply_id' => $request->input('reply_id'),
            'filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => $request->input('user_id', $ticket->customer_id),
        ]);

        return response()->json([
            'success' => true,
            'attachment' => [
                'id' => $attachment->id,
                'filename' => $attachment->filename,
                'file_size' => $attachment->file_size,
                'mime_type' => $attachment->mime_type,
                'url' => asset('storage/' . $path),
                'created_at' => $attachment->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Assign ticket to an admin user
     */
    public function assign(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $ticket->update(['assigned_to' => $data['assigned_to']]);
        $ticket->load(['customer', 'license', 'product', 'replies', 'assignedAdmin']);

        // Send email notification if ticket was assigned
        if ($data['assigned_to'] && $ticket->customer && $ticket->customer->email) {
            \App\Jobs\SendEmailJob::dispatch(
                new \App\Mail\TicketUpdatedMail($ticket, 'assigned'),
                $ticket->customer->email
            );
        }

        return new SupportTicketResource($ticket);
    }
}


