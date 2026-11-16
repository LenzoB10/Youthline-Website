<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class AdvanceRepairRequest extends FormRequest
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
            'actual_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'date' => ['nullable', 'date', 'before_or_equal:today'],
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photos.max' => 'Add up to 8 photos at a time.',
            'photos.*.mimes' => 'Photos must be JPG, PNG or WebP.',
            'photos.*.max' => 'Each photo must be 8 MB or smaller.',
        ];
    }
}
