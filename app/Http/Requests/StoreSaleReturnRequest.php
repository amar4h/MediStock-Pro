<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'                  => 'required|array|min:1',
            'items.*.sale_item_id'   => 'required|integer|exists:sale_items,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'return_type'            => 'required|in:full,partial',
            'reason'                 => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                  => 'At least one item is required for return.',
            'items.min'                       => 'At least one item is required for return.',
            'items.*.sale_item_id.required'   => 'Each return item must reference an original sale item.',
            'items.*.sale_item_id.exists'     => 'One or more referenced sale items do not exist.',
            'items.*.quantity.required'       => 'Each return item must have a quantity.',
            'items.*.quantity.min'            => 'Return quantity must be at least 1.',
            'return_type.required'            => 'Return type is required.',
            'return_type.in'                  => 'Return type must be either full or partial.',
        ];
    }
}
