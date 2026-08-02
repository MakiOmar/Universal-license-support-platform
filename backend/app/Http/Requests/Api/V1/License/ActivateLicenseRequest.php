<?php

namespace App\Http\Requests\Api\V1\License;

use Illuminate\Foundation\Http\FormRequest;

class ActivateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string'],
            'activation_type' => ['required', 'string', 'max:50'],
            'activation_value' => ['required', 'string', 'max:255'],
        ];
    }
}
