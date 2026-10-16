<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m'],
            'availability_deadline' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return ['month.required' => 'Escolha o mês.'];
    }
}
