<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ticket\StoreTicketReplyRequest;
use App\Http\Requests\Api\V1\Ticket\StoreTicketRequest;
use App\Http\Resources\Api\V1\SupportTicketResource;
use App\Http\Resources\Api\V1\TicketReplyResource;
use App\Models\SupportTicket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerTicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SupportTicket::class);

        $tickets = $request->user()
            ->tickets()
            ->with(['product', 'license'])
            ->latest()
            ->get();

        return SupportTicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request): SupportTicketResource
    {
        $this->authorize('create', SupportTicket::class);

        $data = $request->validated();

        if (isset($data['license_id'])) {
            $ownsLicense = $request->user()->licenses()->where('id', $data['license_id'])->exists();
            abort_unless($ownsLicense, 403);
        }

        $ticket = $this->ticketService->create($request->user(), $data);

        return new SupportTicketResource($ticket);
    }

    public function show(Request $request, SupportTicket $ticket): SupportTicketResource
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'product',
            'license',
            'replies' => fn ($q) => $q->where('is_internal', false)->orderBy('created_at'),
        ]);

        return new SupportTicketResource($ticket);
    }

    public function reply(StoreTicketReplyRequest $request, SupportTicket $ticket): TicketReplyResource
    {
        $this->authorize('reply', $ticket);

        $reply = $this->ticketService->reply($ticket, $request->user(), $request->validated('message'));

        return new TicketReplyResource($reply);
    }
}
