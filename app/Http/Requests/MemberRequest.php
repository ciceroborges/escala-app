<?php

namespace App\Http\Requests;

use App\Enums\MemberRole;
use App\Enums\Permission;
use App\Support\CurrentMinistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->email)),
            'unlimited' => $this->boolean('unlimited'),
            'active' => $this->has('active') ? $this->boolean('active') : true,
        ]);
    }

    public function rules(): array
    {
        $ministryId = app(CurrentMinistry::class)->id();
        $position = Rule::exists('positions', 'id')->where('ministry_id', $ministryId);

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::enum(MemberRole::class)],
            'permissions' => ['array'],
            'permissions.*' => [Rule::enum(Permission::class)],
            'positions' => ['array'],
            'positions.*' => ['integer', $position],
            'fixed_position_id' => ['nullable', 'integer', $position],
            'unlimited' => ['boolean'],
            'monthly_limit' => ['nullable', 'required_if:unlimited,false', 'integer', 'min:1', 'max:31'],
            'active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome.',
            'email.required' => 'Informe o e-mail — é com ele que a pessoa entra no app.',
            'email.email' => 'E-mail inválido.',
            'monthly_limit.required_if' => 'Informe quantas vezes por mês, ou marque "sem limite".',
            'monthly_limit.min' => 'O limite precisa ser pelo menos 1.',
        ];
    }
}
