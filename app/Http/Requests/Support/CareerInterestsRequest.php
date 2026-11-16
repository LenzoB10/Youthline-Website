<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class CareerInterestsRequest extends FormRequest
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
            'interests' => ['present', 'array', 'max:10'],
            'interests.*' => ['required', 'string', 'max:80', 'distinct:ignore_case'],
        ];
    }
}
