<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'expense_category_id' => [
                'sometimes',
                'integer',
                Rule::exists('expense_categories', 'id')->where('tenant_id', $tenantId),
            ],
            'amount'              => 'sometimes|numeric|min:0.01',
            'expense_date'        => 'sometimes|date',
            'description'         => 'nullable|string|max:1000',
            'payment_mode'        => 'sometimes|in:cash,upi,bank_transfer,cheque',
        ];
    }
}
