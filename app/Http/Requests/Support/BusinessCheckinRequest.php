<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class BusinessCheckinRequest extends FormRequest
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
            'checkin_date' => ['required', 'date', 'before_or_equal:today'],
            'is_running' => ['required', 'boolean'],
            'monthly_income' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
