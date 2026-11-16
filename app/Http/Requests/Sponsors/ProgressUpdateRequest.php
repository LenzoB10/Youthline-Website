<?php

namespace App\Http\Requests\Sponsors;

use Illuminate\Foundation\Http\FormRequest;

class ProgressUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'progress_update' => ['required', 'string', 'max:1500'],
        ];
    }
}
