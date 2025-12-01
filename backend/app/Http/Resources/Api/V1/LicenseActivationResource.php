<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseActivationResource extends JsonResource
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
            'activation_type' => $this->activation_type,
            'activation_value' => $this->activation_value,
            'ip_address' => $this->ip_address,
            'status' => $this->status,
            'activated_at' => $this->activated_at?->toIso8601String(),
            'last_check' => $this->last_check?->toIso8601String(),
        ];
    }
}

