<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        $itemId   = $this->route('item');

        return [
            'name'            => 'sometimes|string|max:255',
            'composition'     => 'nullable|string|max:500',
            'category_id'     => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('tenant_id', $tenantId),
            ],
            'manufacturer_id' => [
                'nullable',
                'integer',
                Rule::exists('manufacturers', 'id')->where('tenant_id', $tenantId),
            ],
            'hsn_code'        => 'nullable|string|max:20',
            'gst_percent'     => 'sometimes|numeric|in:0,5,12,18,28',
            'default_margin'  => 'nullable|numeric|min:0|max:100',
            'barcode'         => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('items', 'barcode')
                    ->where('tenant_id', $tenantId)
                    ->ignore($itemId),
            ],
            'unit'            => 'nullable|string|max:50',
            'schedule'        => 'nullable|string|max:10',
            'is_active'       => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'gst_percent.in' => 'GST percentage must be one of: 0, 5, 12, 18, or 28.',
            'barcode.unique' => 'This barcode is already in use for another item.',
        ];
    }
}
