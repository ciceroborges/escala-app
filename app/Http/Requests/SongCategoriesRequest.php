<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SongCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $level = fn (string $l) => [
            "{$l}.name" => ['required', 'string', 'max:40'],
            "{$l}.active" => ['boolean'],
            "{$l}.options" => ['array', 'max:12'],
            "{$l}.options.*.id" => ['nullable', 'integer'],
            "{$l}.options.*.name" => ['nullable', 'string', 'max:40'],
            "{$l}.options.*.emoji" => ['nullable', 'string', 'max:16'],
        ];

        return [...$level('primary'), ...$level('secondary')];
    }

    public function messages(): array
    {
        return [
            'primary.name.required' => 'Dê um nome à classificação primária.',
            'secondary.name.required' => 'Dê um nome à classificação secundária.',
        ];
    }
}
