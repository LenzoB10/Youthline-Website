<?php

namespace App\Http\Requests\Support;

use App\Enums\FamilySupportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FamilySupportRequest extends FormRequest
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
            'type' => ['required', Rule::enum(FamilySupportType::class)],
            'description' => ['required', 'string', 'max:250'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'note' => ['nullable', 'string', 'max:255'],
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
            'description.required' => 'Say what was given.',
            'photos.max' => 'Add up to 8 photos at a time.',
            'photos.*.mimes' => 'Photos must be JPG, PNG or WebP.',
            'photos.*.max' => 'Each photo must be 8 MB or smaller.',
        ];
    }
}
