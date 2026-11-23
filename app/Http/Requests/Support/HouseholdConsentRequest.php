<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class HouseholdConsentRequest extends FormRequest
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
            'photo_consent' => ['required', 'boolean'],
            // When the family agrees, record the day they signed.
            'photo_consent_on' => ['nullable', 'required_if_accepted:photo_consent', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['photo_consent_on.required_if_accepted' => 'Enter the date the family gave consent.'];
    }
}
