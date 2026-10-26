<?php

namespace App\Http\Requests;

use App\Credentials\Registry\CredentialRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Credential::class);
    }

    public function rules(): array
    {
        $registry = app(CredentialRegistry::class);
        $validTypes = array_keys($registry->definitions());

        return [
            'type'   => ['required', 'string', Rule::in($validTypes)],
            'name'   => ['required', 'string', 'max:255'],
            'data'   => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'نوع اعتبارنامه الزامی است.',
            'type.in'       => 'نوع اعتبارنامه نامعتبر است.',
            'name.required' => 'نام الزامی است.',
            'data.required' => 'اطلاعات اعتبارنامه الزامی است.',
        ];
    }
}
