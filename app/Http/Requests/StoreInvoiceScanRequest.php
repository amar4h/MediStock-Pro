<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => 'required|file|mimes:jpeg,jpg,png,webp,pdf|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'An invoice image is required.',
            'image.file'     => 'The upload must be a valid file.',
            'image.mimes'    => 'Only JPEG, JPG, PNG, WebP, and PDF files are allowed.',
            'image.max'      => 'The image must not exceed 5 MB.',
        ];
    }
}
