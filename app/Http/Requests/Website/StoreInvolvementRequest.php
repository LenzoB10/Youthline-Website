<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvolvementRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'type' => ['required', Rule::in(['volunteer', 'mentor', 'partner', 'sponsor', 'donor'])],
            'message' => ['nullable', 'string', 'max:5000'],
            'company' => ['nullable', 'string'], // honeypot — must stay empty
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please tell us your name.',
            'email.required' => 'Please provide an email so we can get back to you.',
            'email.email' => 'Please provide a valid email address.',
            'type.required' => 'Please choose how you would like to get involved.',
            'type.in' => 'Please choose a valid option.',
        ];
    }
}
