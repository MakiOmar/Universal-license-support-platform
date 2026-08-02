<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PricingTier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingTierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'currency' => $this->currency,
            'max_activations' => $this->max_activations,
            'billing_cycle' => $this->billing_cycle,
            'billing_cycle_label' => PricingTier::billingCycleOptions()[$this->billing_cycle] ?? $this->billing_cycle,
            'is_recurring' => $this->isRecurring(),
            'is_one_time' => $this->isOneTimePayment(),
            'is_active' => $this->is_active,
        ];
    }
}
