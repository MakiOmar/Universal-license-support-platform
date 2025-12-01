<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivateLicenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string', 'max:255'],
            'activation_type' => ['required', 'string', Rule::in(['domain', 'machine_id', 'device_id', 'api_key'])],
            'activation_value' => ['required', 'string', 'max:255'],
        ];
    }
}

