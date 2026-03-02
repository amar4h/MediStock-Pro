<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'name'     => 'required|string|max:255',
            'email'    => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where('tenant_id', $tenantId),
            ],
            'phone'    => 'nullable|string|max:20',
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'role_id'  => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where('tenant_id', $tenantId),
            ],
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'User name is required.',
            'email.required'    => 'Email address is required.',
            'email.email'       => 'Please provide a valid email address.',
            'email.unique'      => 'This email is already registered for this store.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role_id.required'  => 'User role is required.',
            'role_id.exists'    => 'Selected role does not exist.',
        ];
    }
}
