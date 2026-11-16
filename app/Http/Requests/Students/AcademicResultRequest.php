<?php

namespace App\Http\Requests\Students;

use App\Enums\ResultType;
use App\Models\AcademicResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AcademicResultRequest extends FormRequest
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
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'exam_type' => ['required', Rule::enum(ResultType::class)],
            // Term/university: average out of 100. MSCE: aggregate points of the best six (6 = best, 54 = lowest).
            'average' => ['nullable', Rule::requiredIf(fn () => $this->type()?->usesAverage() ?? true), 'numeric', 'min:0', 'max:100'],
            'msce_points' => ['nullable', Rule::requiredIf(fn () => $this->type() === ResultType::Msce), 'integer', 'min:6', 'max:54'],
            'position' => ['nullable', 'string', 'max:30'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'grades' => ['nullable', 'array', 'max:20'],
            'grades.*.subject' => ['nullable', 'string', 'max:60'],
            'grades.*.grade' => ['nullable', 'string', 'max:10'],
            'report_card' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                // One result per student, term and exam type.
                $current = $this->route('result');
                $duplicate = AcademicResult::query()
                    ->where('student_id', $this->route('student')->id)
                    ->where('term_id', $this->input('term_id'))
                    ->where('exam_type', $this->input('exam_type'))
                    ->when($current instanceof AcademicResult, fn ($q) => $q->whereKeyNot($current->id))
                    ->exists();

                if ($duplicate) {
                    $validator->errors()->add('term_id', 'A result of this type is already recorded for this term. Edit that one instead.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'average.required' => 'Enter the average (out of 100).',
            'msce_points.required' => 'Enter the MSCE aggregate points.',
            'msce_points.min' => 'MSCE points range from 6 (best) to 54.',
            'msce_points.max' => 'MSCE points range from 6 (best) to 54.',
            'report_card.mimes' => 'Upload the report card as a PDF or a photo (JPG, PNG or WebP).',
            'report_card.max' => 'The report card must be 10 MB or smaller.',
        ];
    }

    protected function type(): ?ResultType
    {
        return ResultType::tryFrom((string) $this->input('exam_type'));
    }
}
