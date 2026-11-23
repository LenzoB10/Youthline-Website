<?php

namespace App\Http\Requests\Support;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DistributionRequest extends FormRequest
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

                if (! $student->status->canReceiveBasicNeeds()) {
                    $validator->errors()->add('amount', "{$student->full_name} has graduated. Graduates can only be supported with business money, not basic needs.");
                }
            },
        ];
    }
}
