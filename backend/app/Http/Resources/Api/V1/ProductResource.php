<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'version' => $this->version,
            'status' => $this->status,
            'pricing_tiers' => PricingTierResource::collection($this->whenLoaded('pricingTiers')),
        ];
    }
}
