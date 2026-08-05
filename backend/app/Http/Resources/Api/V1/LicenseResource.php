<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'license_key' => $this->license_key,
            'status' => $this->status,
            'is_trial' => (bool) $this->is_trial,
            'max_activations' => $this->max_activations,
            'activations_used' => $this->when(
                $this->relationLoaded('activations'),
                fn () => $this->activations->where('status', 'active')->count(),
                fn () => $this->activeActivationsCount(),
            ),
            'purchased_at' => $this->purchased_at,
            'expires_at' => $this->expires_at,
            'support_expires_at' => $this->support_expires_at,
            'product' => new ProductResource($this->whenLoaded('product')),
            'pricing_tier' => new PricingTierResource($this->whenLoaded('pricingTier')),
            'activations' => LicenseActivationResource::collection($this->whenLoaded('activations')),
        ];
    }
}
