<?php

namespace App\Http\Requests\Students;

use App\Enums\EducationLevel;
use App\Services\Settings\AcademicCalendarService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Change a student's school/level for the current academic year.
 */
class PlaceEnrollmentRequest extends FormRequest
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
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'level' => ['required', Rule::enum(EducationLevel::class)],
            'course' => ['nullable', 'string', 'max:255'],
            'is_repeating' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! app(AcademicCalendarService::class)->current()) {
                    $validator->errors()->add('level', 'Set the current academic year in Settings first.');
                }
            },
        ];
    }
}
