<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SongRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalize = fn ($key) => $key ? ucfirst(trim($key)) : null;

        $this->merge([
            'keys' => collect($this->input('keys', []))
                ->filter(fn ($k) => filled($k['member_id'] ?? null))
                ->map(fn ($k) => [
                    'member_id' => $k['member_id'],
                    'musical_key' => $normalize($k['musical_key'] ?? null),
                    'starting_key' => $normalize($k['starting_key'] ?? null),
                ])->values()->all(),
        ]);
    }

    public function rules(): array
    {
        $key = ['nullable', 'string', 'max:4', 'regex:/^[A-G][#b]?m?$/'];

        return [
            'title' => ['required', 'string', 'max:160'],
            'artist' => ['nullable', 'string', 'max:160'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'chords_url' => ['nullable', 'url', 'max:255'],
            'primary_option_id' => ['nullable', 'integer'],
            'secondary_option_id' => ['nullable', 'integer'],
            'keys' => ['array'],
            'keys.*.member_id' => ['required', 'integer'],
            'keys.*.musical_key' => $key,
            'keys.*.starting_key' => $key,
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Informe o nome da música.',
            'youtube_url.url' => 'Cole o link completo do YouTube (começando com https://).',
            'chords_url.url' => 'Cole o link completo da cifra (começando com https://).',
            'keys.*.musical_key.regex' => 'Tom inválido. Use C, D, Eb, F#, Am…',
            'keys.*.starting_key.regex' => 'Tom inválido. Use C, D, Eb, F#, Am…',
        ];
    }
}
