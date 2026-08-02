<?php

namespace App\Http\Requests\Api\V1\License;

use Illuminate\Foundation\Http\FormRequest;

class DeactivateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string'],
            'activation_hash' => ['required', 'string', 'size:64'],
        ];
    }
}
