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
        $customerId = $this->user()?->id;

        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'category' => ['nullable', Rule::in([
                'technical', 'billing', 'feature_request', 'bug_report', 'account', 'license',
            ])],
            // Must be a product the customer already owns a license for.
            'product_id' => [
                'required',
                'integer',
                Rule::exists('licenses', 'product_id')->where(
                    fn ($query) => $query->where('customer_id', $customerId),
                ),
            ],
            'license_id' => [
                'nullable',
                'integer',
                Rule::exists('licenses', 'id')->where(function ($query) use ($customerId) {
                    $query->where('customer_id', $customerId);

                    if ($this->filled('product_id')) {
                        $query->where('product_id', $this->integer('product_id'));
                    }
                }),
            ],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => __('Please select a licensed product.'),
            'product_id.exists' => __('You can only open tickets for products you are licensed to use.'),
            'license_id.exists' => __('The selected license is invalid for this product.'),
        ];
    }
}
