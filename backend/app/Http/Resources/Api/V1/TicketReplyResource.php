<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketReplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->is_internal) {
            return [];
        }

        return [
            'id' => $this->id,
            'message' => $this->message,
            'author_type' => class_basename($this->author_type),
            'created_at' => $this->created_at,
        ];
    }
}
