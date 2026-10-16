<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'emoji' => ['nullable', 'string', 'max:16'],
            'uses_microphone' => ['boolean'],
            'is_vocal' => ['boolean'],
            'min_per_service' => ['required', 'integer', 'min:0', 'max:20'],
            'max_per_service' => ['required', 'integer', 'min:0', 'max:20', 'gte:min_per_service'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome da função.',
            'max_per_service.gte' => 'O máximo não pode ser menor que o mínimo.',
        ];
    }
}
