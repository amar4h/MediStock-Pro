<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where('tenant_id', $tenantId),
            ],

            // Sale items
            'items'                      => 'required|array|min:1',
            'items.*.item_id'            => [
                'required',
                'integer',
                Rule::exists('items', 'id')->where('tenant_id', $tenantId),
            ],
            'items.*.batch_id'           => [
                'nullable',
                'integer',
                Rule::exists('batches', 'id')->where('tenant_id', $tenantId),
            ],
            'items.*.quantity'           => 'required|integer|min:1',
            'items.*.discount_percent'   => 'nullable|numeric|min:0|max:100',

            // Invoice-level
            'invoice_discount' => 'nullable|numeric|min:0',
            'payment_mode'     => 'required|in:cash,credit,partial,upi',
            'paid_amount'      => 'required_if:payment_mode,partial|nullable|numeric|min:0',

            // Optional metadata
            'doctor_name'  => 'nullable|string|max:255',
            'patient_name' => 'nullable|string|max:255',
            'notes'        => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'               => 'At least one item is required.',
            'items.min'                    => 'At least one item is required.',
            'items.*.item_id.required'     => 'Each item must have an item ID.',
            'items.*.item_id.exists'       => 'One or more selected items do not exist.',
            'items.*.quantity.required'    => 'Each item must have a quantity.',
            'items.*.quantity.min'         => 'Quantity must be at least 1.',
            'paid_amount.required_if'      => 'Paid amount is required for partial payments.',
            'payment_mode.in'             => 'Payment mode must be one of: cash, credit, partial, or upi.',
        ];
    }
}
