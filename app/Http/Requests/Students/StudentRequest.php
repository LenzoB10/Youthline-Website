<?php

namespace App\Http\Requests\Students;

use App\Enums\EducationLevel;
use App\Services\Settings\AcademicCalendarService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Add Student / Edit student. School, level and course are only taken when
 * registering — after that they change through promotion or "Change school/level".
 */
class StudentRequest extends FormRequest
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
        $registering = $this->isMethod('post');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before:today', 'after:1970-01-01'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'home_location' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'guardian_relationship' => ['nullable', 'string', 'max:100'],

            'household_mode' => ['required', Rule::in(['none', 'existing', 'new'])],
            'household_id' => ['nullable', 'required_if:household_mode,existing', 'integer', 'exists:households,id'],
            'household' => ['nullable', 'required_if:household_mode,new', 'array'],
            'household.head_name' => ['required_if:household_mode,new', 'nullable', 'string', 'max:255'],
            'household.village_area' => ['nullable', 'string', 'max:255'],
            'household.district' => ['nullable', 'string', 'max:100'],
            'household.phone' => ['nullable', 'string', 'max:30'],
            'household.members_count' => ['nullable', 'integer', 'min:1', 'max:50'],

            // Typed freely; matched to (or added to) the institutions list.
            'institution_name' => [$registering ? 'nullable' : 'prohibited', 'string', 'max:255'],
            'level' => [$registering ? 'nullable' : 'prohibited', Rule::enum(EducationLevel::class)],
            'course' => [$registering ? 'nullable' : 'prohibited', 'string', 'max:255'],

            'program_ids' => ['present', 'array'],
            'program_ids.*' => ['integer', 'distinct', 'exists:programs,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'household_id.required_if' => 'Choose the household this student belongs to.',
            'household.head_name.required_if' => 'Enter the name of the head of the household.',
        ];
    }

    /**
     * A level belongs to an academic year, so the current year must be set first.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->filled('level') && ! app(AcademicCalendarService::class)->current()) {
                    $validator->errors()->add('level', 'Set the current academic year in Settings before recording a level.');
                }
            },
        ];
    }
}
