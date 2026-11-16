<?php

namespace App\Http\Requests\Sponsors;

use App\Models\Sponsorship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SponsorshipRequest extends FormRequest
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
        $sponsorship = $this->route('sponsorship');

        return [
            // Only when adding: a student can't be sponsored twice by the same sponsor at once.
            'student_id' => [
                Rule::requiredIf(! $sponsorship instanceof Sponsorship), 'integer', 'exists:students,id',
                Rule::unique('sponsorships', 'student_id')
                    ->where('sponsor_id', $this->route('sponsor')->id)
                    ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()))
                    ->ignore($sponsorship instanceof Sponsorship ? $sponsorship->id : null),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'amount_per_year' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['student_id.unique' => 'This sponsor already supports this student.'];
    }
}
