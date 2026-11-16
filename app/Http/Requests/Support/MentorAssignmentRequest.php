<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MentorAssignmentRequest extends FormRequest
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
            // null ends the current assignment
            'mentor_id' => ['nullable', 'integer', Rule::exists('mentors', 'id')->where('is_active', true)],
            'start_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
