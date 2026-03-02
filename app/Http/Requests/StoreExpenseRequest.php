<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('expense_categories', 'id')->where('tenant_id', $tenantId),
            ],
            'amount'              => 'required|numeric|min:0.01',
            'expense_date'        => 'required|date',
            'description'         => 'nullable|string|max:1000',
            'payment_mode'        => 'required|in:cash,upi,bank_transfer,cheque',
        ];
    }

    public function messages(): array
    {
        return [
            'expense_category_id.required' => 'Expense category is required.',
            'expense_category_id.exists'   => 'Selected expense category does not exist.',
            'amount.required'              => 'Expense amount is required.',
            'amount.min'                   => 'Expense amount must be at least 0.01.',
            'expense_date.required'        => 'Expense date is required.',
            'payment_mode.required'        => 'Payment mode is required.',
            'payment_mode.in'              => 'Payment mode must be one of: cash, upi, bank_transfer, or cheque.',
        ];
    }
}
