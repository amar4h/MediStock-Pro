<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        $userId   = $this->route('user');

        return [
            'name'     => 'sometimes|string|max:255',
            'email'    => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where('tenant_id', $tenantId)
                    ->ignore($userId),
            ],
            'phone'    => 'nullable|string|max:20',
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)],
            'role_id'  => [
                'sometimes',
                'integer',
                Rule::exists('roles', 'id')->where('tenant_id', $tenantId),
            ],
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.email'        => 'Please provide a valid email address.',
            'email.unique'       => 'This email is already registered for this store.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role_id.exists'     => 'Selected role does not exist.',
        ];
    }
}
