<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class GroupHandoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'given_on' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'note' => ['nullable', 'string', 'max:255'],
            'student_ids' => ['required', 'array', 'min:1', 'max:2000'],
            'student_ids.*' => ['integer', 'distinct', 'exists:students,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_ids.required' => 'Tick at least one student.',
            'student_ids.min' => 'Tick at least one student.',
        ];
    }
}
