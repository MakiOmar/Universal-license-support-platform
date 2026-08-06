<?php

namespace App\Http\Requests\Api\V1\License;

use App\Http\Requests\Api\V1\License\Concerns\NormalizesDeviceMeta;
use Illuminate\Foundation\Http\FormRequest;

class ValidateLicenseRequest extends FormRequest
{
    use NormalizesDeviceMeta;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string'],
            'activation_type' => ['nullable', 'string', 'max:50'],
            'activation_value' => ['nullable', 'string', 'max:255'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'platform' => ['sometimes', 'nullable', 'string', 'max:50'],
            'app_version' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }
}
