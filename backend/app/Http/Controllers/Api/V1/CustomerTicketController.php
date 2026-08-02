<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ticket\StoreTicketReplyRequest;
use App\Http\Requests\Api\V1\Ticket\StoreTicketRequest;
use App\Http\Resources\Api\V1\SupportTicketResource;
use App\Http\Resources\Api\V1\TicketReplyResource;
use App\Models\SupportTicket;
use App\Models\TicketAttachment;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerTicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SupportTicket::class);

        $query = $request->user()
            ->tickets()
            ->with(['product', 'license']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $tickets = $query->latest()->get();

        return SupportTicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request): SupportTicketResource
    {
        $this->authorize('create', SupportTicket::class);

        $data = $request->validated();

        // Extra ownership checks (Form Request already scopes exists rules to this customer).
        if (! empty($data['license_id'])) {
            $ownsLicense = $request->user()->licenses()
                ->where('id', $data['license_id'])
                ->where('product_id', $data['product_id'])
                ->exists();
            abort_unless($ownsLicense, 403);
        }

        $files = $request->file('attachments', []);
        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }

        $ticket = $this->ticketService->create($request->user(), $data, $files);

        return new SupportTicketResource($ticket);
    }

    public function show(Request $request, SupportTicket $ticket): SupportTicketResource
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'product',
            'license',
            'attachments',
            'replies' => fn ($q) => $q->where('is_internal', false)->with('attachments')->orderBy('created_at'),
        ]);

        return new SupportTicketResource($ticket);
    }

    public function reply(StoreTicketReplyRequest $request, SupportTicket $ticket): TicketReplyResource
    {
        $this->authorize('reply', $ticket);

        $files = $request->file('attachments', []);
        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }

        $reply = $this->ticketService->reply(
            $ticket,
            $request->user(),
            $request->validated('message'),
            false,
            $files,
        );

        return new TicketReplyResource($reply);
    }

    public function downloadAttachment(
        Request $request,
        SupportTicket $ticket,
        TicketAttachment $attachment,
    ): StreamedResponse {
        $this->authorize('view', $ticket);
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->filename);
    }
}
