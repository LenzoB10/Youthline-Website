<?php

namespace App\Http\Requests\Students;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Permanent deletion wipes a student's whole history, so the Admin must type the
 * student's full name to confirm.
 */
class DeleteStudentRequest extends FormRequest
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
        /** @var Student $student */
        $student = $this->route('student');

        return [
            'confirm_name' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) use ($student) {
                if (mb_strtolower(trim((string) $value)) !== mb_strtolower($student->full_name)) {
                    $fail("Type the student's full name exactly to confirm.");
                }
            }],
        ];
    }
}
