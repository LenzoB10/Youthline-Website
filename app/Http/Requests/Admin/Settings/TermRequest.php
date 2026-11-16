<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TermRequest extends FormRequest
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
        /** @var Term|null $term */
        $term = $this->route('term');
        $yearId = $term?->academic_year_id ?? $this->input('academic_year_id');

        return [
            'academic_year_id' => [$term ? 'prohibited' : 'required', 'integer', 'exists:academic_years,id'],
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('terms', 'name')->where('academic_year_id', $yearId)->ignore($term),
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * Term dates must fall inside their academic year, when both are known.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $year = $this->route('term')?->academicYear
                    ?? AcademicYear::query()->find($this->input('academic_year_id'));

                if (! $year) {
                    return;
                }

                foreach (['start_date', 'end_date'] as $field) {
                    $date = $this->date($field);

                    if (! $date || $validator->errors()->has($field)) {
                        continue;
                    }

                    if (($year->start_date && $date->lt($year->start_date)) || ($year->end_date && $date->gt($year->end_date))) {
                        $validator->errors()->add($field, "The date must fall within academic year {$year->name}.");
                    }
                }
            },
        ];
    }
}
