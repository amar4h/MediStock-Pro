<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseRequest extends FormRequest
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
                'sometimes',
                'integer',
                Rule::exists('suppliers', 'id')->where('tenant_id', $tenantId),
            ],
            'invoice_number' => 'sometimes|string|max:100',
            'invoice_date'   => 'sometimes|date',
            'notes'          => 'nullable|string',

            // Items (optional for update — only if modifying line items)
            'items'                    => 'sometimes|array|min:1',
            'items.*.item_id'          => [
                'required_with:items',
                'integer',
                Rule::exists('items', 'id')->where('tenant_id', $tenantId),
            ],
            'items.*.batch_number'     => 'required_with:items|string|max:100',
            'items.*.expiry_date'      => 'required_with:items|date|after:today',
            'items.*.quantity'         => 'required_with:items|integer|min:1',
            'items.*.free_quantity'    => 'nullable|integer|min:0',
            'items.*.mrp'             => 'required_with:items|numeric|min:0',
            'items.*.purchase_price'   => 'required_with:items|numeric|min:0',
            'items.*.selling_price'    => 'required_with:items|numeric|min:0',
            'items.*.gst_percent'      => 'required_with:items|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',

            // Payment
            'payment_mode' => 'sometimes|in:cash,credit,partial',
            'paid_amount'  => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
        ];
    }
}
