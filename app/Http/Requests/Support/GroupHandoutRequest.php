<?php

namespace App\Http\Requests\Support;

use App\Enums\StudentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            // Graduates can't receive basic needs.
            'student_ids.*' => ['integer', 'distinct', Rule::exists('students', 'id')->whereNot('status', StudentStatus::Graduated->value)],
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
            'student_ids.*.exists' => 'Graduated students can only be supported with business money, not basic needs.',
        ];
    }
}
