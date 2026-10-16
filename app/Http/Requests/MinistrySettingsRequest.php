<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MinistrySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'church_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return ['name.required' => 'Dê um nome ao ministério.'];
    }
}
