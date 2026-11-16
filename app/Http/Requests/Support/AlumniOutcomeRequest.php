<?php

namespace App\Http\Requests\Support;

use App\Enums\AlumniOutcomeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlumniOutcomeRequest extends FormRequest
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
            'recorded_on' => ['required', 'date', 'before_or_equal:today'],
            'outcome' => ['required', Rule::enum(AlumniOutcomeType::class)],
            'details' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
