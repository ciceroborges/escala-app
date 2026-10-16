<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetlistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalize = fn ($key) => $key ? ucfirst(trim($key)) : null;

        if (is_array($this->singers) && isset($this->singers[0]) && is_array($this->singers[0])) {
            $this->merge(['singers' => array_map(fn ($s) => [
                'member_id' => $s['member_id'] ?? null,
                'musical_key' => $normalize($s['musical_key'] ?? null),
                'starting_key' => $normalize($s['starting_key'] ?? null),
            ], $this->singers)]);
        }
    }

    public function rules(): array
    {
        $key = ['nullable', 'string', 'max:4', 'regex:/^[A-G][#b]?m?$/'];

        return [
            'song_id' => [$this->isMethod('post') ? 'required' : 'prohibited', 'integer'],
            'notes' => ['nullable', 'string', 'max:160'],
            'singers' => ['array'],
            // na criação vem só a lista de ids; na edição vem cada voz com tom
            'singers.*' => $this->isMethod('post') ? ['integer'] : ['array'],
            'singers.*.member_id' => $this->isMethod('post') ? [] : ['required', 'integer'],
            'singers.*.musical_key' => $this->isMethod('post') ? [] : $key,
            'singers.*.starting_key' => $this->isMethod('post') ? [] : $key,
        ];
    }

    public function messages(): array
    {
        return [
            'song_id.required' => 'Escolha a música.',
            'singers.*.musical_key.regex' => 'Tom inválido. Use C, D, Eb, F#, Am…',
            'singers.*.starting_key.regex' => 'Tom inválido. Use C, D, Eb, F#, Am…',
        ];
    }
}
