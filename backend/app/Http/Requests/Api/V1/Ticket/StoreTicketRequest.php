<?php

namespace App\Http\Requests\Api\V1\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'category' => ['nullable', Rule::in([
                'technical', 'billing', 'feature_request', 'bug_report', 'account', 'license',
            ])],
            'license_id' => ['nullable', 'integer', 'exists:licenses,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }
}
