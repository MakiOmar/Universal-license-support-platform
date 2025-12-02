<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'license' => new LicenseResource($this->whenLoaded('license')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'subject' => $this->subject,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'category' => $this->category,
            'assigned_to' => $this->assigned_to,
            'replies' => TicketReplyResource::collection($this->whenLoaded('replies')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }
}

