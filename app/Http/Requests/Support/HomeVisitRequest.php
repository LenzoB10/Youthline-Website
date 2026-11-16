<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class HomeVisitRequest extends FormRequest
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
            'visit_date' => ['required', 'date', 'before_or_equal:today'],
            'members_count' => ['nullable', 'integer', 'min:1', 'max:60'],
            'living_conditions' => ['nullable', 'string', 'max:2000'],
            'income_sources' => ['nullable', 'string', 'max:1000'],
            'needs_identified' => ['nullable', 'string', 'max:2000'],
            'next_visit_due' => ['nullable', 'date', 'after:visit_date'],
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
