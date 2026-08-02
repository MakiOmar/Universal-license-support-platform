<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseActivationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'activation_type' => $this->activation_type,
            'activation_value' => $this->activation_value,
            'activation_hash' => $this->activation_hash,
            'status' => $this->status,
            'activated_at' => $this->activated_at,
            'last_check_at' => $this->last_check_at,
        ];
    }
}
