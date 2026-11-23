<?php

namespace App\Http\Requests\Students;

use App\Enums\StudentStatus;
use App\Models\ExitReason;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ChangeStudentStatusRequest extends FormRequest
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
        $to = StudentStatus::tryFrom((string) $this->input('status'));

        return [
            'status' => ['required', Rule::enum(StudentStatus::class)],
            'exit_reason' => [
                $to?->requiresReason() ? 'required' : 'nullable',
                'string',
                Rule::in(ExitReason::query()->active()->pluck('name')),
            ],
            'exit_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'exit_reason.required' => 'Choose a reason — leaving always needs one.',
            'exit_reason.in' => 'Choose one of the active exit reasons.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var Student $student */
                $student = $this->route('student');
                $to = StudentStatus::tryFrom((string) $this->input('status'));

                if ($to && ! $student->status->canMoveTo($to)) {
                    $validator->errors()->add(
                        'status',
                        "A student who is {$student->status->label()} can't be changed to {$to->label()}."
                    );
                } elseif ($to && ! in_array($to, $student->availableStatuses(), true)) {
                    $validator->errors()->add('status', 'Only students in Year 4 or Year 5 can be marked as graduated.');
                }
            },
        ];
    }
}
