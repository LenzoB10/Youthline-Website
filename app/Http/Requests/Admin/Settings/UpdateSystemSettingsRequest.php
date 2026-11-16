<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemSettingsRequest extends FormRequest
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
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'require_payment_approval' => ['required', 'boolean'],
            'payment_approval_limit' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'require_scholarship_approval' => ['required', 'boolean'],
            'require_repair_approval' => ['required', 'boolean'],
            'repair_approval_limit' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'grade_drop_threshold' => ['sometimes', 'required', 'numeric', 'min:1', 'max:100'],
            'mentorship_overdue_days' => ['sometimes', 'required', 'integer', 'min:7', 'max:365'],
            'home_visit_interval_months' => ['sometimes', 'required', 'integer', 'min:1', 'max:36'],
            'business_checkin_days' => ['sometimes', 'required', 'integer', 'min:7', 'max:365'],
            'counselling_notes_visibility' => ['required', Rule::in(['author', 'all_managers'])],
        ];
    }
}
