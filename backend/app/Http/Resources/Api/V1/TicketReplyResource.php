<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketReplyResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user_type' => $this->user_type,
            'message' => $this->message,
            'is_internal' => $this->is_internal,
            'attachments' => $this->when($this->relationLoaded('attachments'), function () {
                return $this->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'filename' => $attachment->filename,
                        'file_size' => $attachment->file_size,
                        'mime_type' => $attachment->mime_type,
                        'url' => asset('storage/' . $attachment->file_path),
                        'created_at' => $attachment->created_at?->toIso8601String(),
                    ];
                });
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

