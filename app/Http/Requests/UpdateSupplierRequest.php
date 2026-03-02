<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => 'sometimes|string|max:255',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
            'gstin'           => 'nullable|string|max:20',
            'drug_license_no' => 'nullable|string|max:100',
            'address'         => 'nullable|string|max:1000',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_active'       => 'nullable|boolean',
        ];
    }
}
