<?php

namespace App\Http\Requests\Admin\Website;

use App\Models\Consent;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NewsPostRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['news', 'success_story', 'activity'])],
            'body' => ['nullable', 'string', 'max:20000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'is_published' => ['boolean'],
        ];
    }

    /**
     * A student's story may only be published if consent is on file.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('is_published') || ! $this->filled('student_id')) {
                return;
            }

            $allowed = Consent::query()
                ->where('student_id', $this->integer('student_id'))
                ->where('story_allowed', true)
                ->exists();

            if (! $allowed) {
                $validator->errors()->add(
                    'is_published',
                    'This student has not consented to their story being published.',
                );
            }
        });
    }
}
