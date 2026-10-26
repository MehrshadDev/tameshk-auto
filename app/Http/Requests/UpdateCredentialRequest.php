<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('credential'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'data' => ['sometimes', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام الزامی است.',
        ];
    }
}
