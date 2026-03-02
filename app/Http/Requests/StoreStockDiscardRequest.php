<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockDiscardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'item_id' => [
                'required',
                'integer',
                Rule::exists('items', 'id')->where('tenant_id', $tenantId),
            ],
            'batch_id' => [
                'required',
                'integer',
                Rule::exists('batches', 'id')->where('tenant_id', $tenantId),
            ],
            'quantity'     => 'required|integer|min:1',
            'reason'       => 'required|in:expired,damaged,lost,other',
            'notes'        => 'nullable|string|max:1000',
            'discard_date' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.required'  => 'Item is required.',
            'item_id.exists'    => 'Selected item does not exist.',
            'batch_id.required' => 'Batch is required.',
            'batch_id.exists'   => 'Selected batch does not exist.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min'      => 'Quantity must be at least 1.',
            'reason.required'   => 'Reason for discard is required.',
            'reason.in'         => 'Reason must be one of: expired, damaged, lost, or other.',
            'discard_date.required' => 'Discard date is required.',
        ];
    }
}
