<?php

namespace App\Http\Requests\Students;

use App\Enums\DocumentType;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Guardian consent. Allowing a photo or story on the public website requires the
 * signed consent form (one of THIS student's consent documents) to be linked.
 */
class RecordConsentRequest extends FormRequest
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
        /** @var Student $student */
        $student = $this->route('student');
        $grantsSomething = $this->boolean('photo_allowed') || $this->boolean('story_allowed');

        return [
            'photo_allowed' => ['required', 'boolean'],
            'story_allowed' => ['required', 'boolean'],
            'signed_on' => [$grantsSomething ? 'required' : 'nullable', 'date', 'before_or_equal:today'],
            'document_id' => [
                $grantsSomething ? 'required' : 'nullable',
                'integer',
                Rule::exists('documents', 'id')
                    ->where('student_id', $student->id)
                    ->where('type', DocumentType::Consent->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_id.required' => 'Link the signed consent form. Upload it under Documents first if needed.',
            'document_id.exists' => 'Choose one of this student\'s consent forms.',
            'signed_on.required' => 'Enter the date the guardian signed the form.',
        ];
    }
}
