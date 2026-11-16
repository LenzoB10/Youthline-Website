<?php

namespace App\Http\Requests\Support;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationRequest extends FormRequest
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
            'type' => ['required', Rule::enum(ApplicationType::class)],
            'organisation' => ['required', 'string', 'max:150'],
            'course' => ['nullable', 'string', 'max:150'],
            'applied_on' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
