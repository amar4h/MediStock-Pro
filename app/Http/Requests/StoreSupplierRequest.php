<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
            'gstin'           => 'nullable|string|max:20',
            'drug_license_no' => 'nullable|string|max:100',
            'address'         => 'nullable|string|max:1000',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_active'       => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Supplier name is required.',
            'email.email'   => 'Please provide a valid email address.',
        ];
    }
}
