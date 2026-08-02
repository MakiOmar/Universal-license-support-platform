<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tier = $this->whenLoaded('pricingTier');

        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'gateway' => $this->gateway,
            'gateway_reference' => $this->gateway_reference,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
            'pricing_tier' => $this->when($this->relationLoaded('pricingTier'), fn () => new PricingTierResource($this->pricingTier)),
            'product' => $this->when(
                $this->relationLoaded('pricingTier') && $this->pricingTier?->relationLoaded('product'),
                fn () => new ProductResource($this->pricingTier->product),
            ),
            'license' => new LicenseResource($this->whenLoaded('license')),
        ];
    }
}
