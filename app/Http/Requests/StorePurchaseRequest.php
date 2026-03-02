<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'supplier_id'   => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where('tenant_id', $tenantId),
            ],
            'invoice_number' => 'required|string|max:100',
            'invoice_date'   => 'required|date',
            'notes'          => 'nullable|string',

            // Purchase items
            'items'                    => 'required|array|min:1',
            'items.*.item_id'          => [
                'required',
                'integer',
                Rule::exists('items', 'id')->where('tenant_id', $tenantId),
            ],
            'items.*.batch_number'     => 'required|string|max:100',
            'items.*.expiry_date'      => 'required|date|after:today',
            'items.*.quantity'         => 'required|integer|min:1',
            'items.*.free_quantity'    => 'nullable|integer|min:0',
            'items.*.mrp'             => 'required|numeric|min:0',
            'items.*.purchase_price'   => 'required|numeric|min:0',
            'items.*.selling_price'    => 'required|numeric|min:0',
            'items.*.gst_percent'      => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',

            // Payment
            'payment_mode' => 'required|in:cash,credit,partial',
            'paid_amount'  => 'required_if:payment_mode,partial|nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                => 'At least one item is required.',
            'items.min'                     => 'At least one item is required.',
            'items.*.item_id.required'      => 'Each item must have an item ID.',
            'items.*.item_id.exists'        => 'One or more selected items do not exist.',
            'items.*.batch_number.required' => 'Each item must have a batch number.',
            'items.*.expiry_date.required'  => 'Each item must have an expiry date.',
            'items.*.expiry_date.after'     => 'Expiry date must be a future date.',
            'items.*.quantity.required'     => 'Each item must have a quantity.',
            'items.*.quantity.min'          => 'Quantity must be at least 1.',
            'items.*.mrp.required'          => 'Each item must have an MRP.',
            'items.*.purchase_price.required' => 'Each item must have a purchase price.',
            'items.*.selling_price.required'  => 'Each item must have a selling price.',
            'paid_amount.required_if'       => 'Paid amount is required for partial payments.',
        ];
    }
}
