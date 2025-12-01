<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLicenseRequest extends FormRequest
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
            'license_key' => ['nullable', 'string', 'max:255', 'unique:licenses,license_key'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'license_type' => ['required', 'string', 'max:50'],
            'max_activations' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(['pending', 'active', 'expired', 'suspended', 'cancelled'])],
            'purchased_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:purchased_at'],
            'support_expires_at' => ['nullable', 'date', 'after_or_equal:purchased_at'],
        ];
    }
}

