<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignments' => ['array'],
            'assignments.*.member_id' => ['required', 'integer'],
            'assignments.*.position_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
