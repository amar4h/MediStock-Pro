<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'                       => 'required|array|min:1',
            'items.*.purchase_item_id'    => 'required|integer|exists:purchase_items,id',
            'items.*.quantity'            => 'required|integer|min:1',
            'reason'                      => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                       => 'At least one item is required for return.',
            'items.min'                            => 'At least one item is required for return.',
            'items.*.purchase_item_id.required'    => 'Each return item must reference an original purchase item.',
            'items.*.purchase_item_id.exists'      => 'One or more referenced purchase items do not exist.',
            'items.*.quantity.required'            => 'Each return item must have a quantity.',
            'items.*.quantity.min'                 => 'Return quantity must be at least 1.',
        ];
    }
}
