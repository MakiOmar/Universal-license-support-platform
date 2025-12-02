<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseResource extends JsonResource
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
            'license_key' => $this->license_key,
            'product' => new ProductResource($this->whenLoaded('product')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'license_type' => $this->license_type,
            'max_activations' => $this->max_activations,
            'current_activations' => $this->whenLoaded('activations', fn () => $this->activations->where('status', 'active')->count()),
            'status' => $this->status,
            'purchased_at' => $this->purchased_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'support_expires_at' => $this->support_expires_at?->toIso8601String(),
            'activations' => LicenseActivationResource::collection($this->whenLoaded('activations')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

