<?php

namespace App\Http\Requests\Support;

use App\Enums\SessionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MentorshipSessionRequest extends FormRequest
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
            'session_date' => ['required', 'date', 'before_or_equal:today'],
            'type' => ['required', Rule::enum(SessionType::class)],
            'mentor_id' => ['nullable', 'integer', 'exists:mentors,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'follow_up_needed' => ['sometimes', 'boolean'],
        ];
    }
}
