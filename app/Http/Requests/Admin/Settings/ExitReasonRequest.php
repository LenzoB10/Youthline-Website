<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\ExitReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExitReasonRequest extends FormRequest
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
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('exit_reasons', 'name')->ignore($this->route('exit_reason')),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Students store the reason's text, so a reason already on record keeps its name.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var ExitReason|null $reason */
                $reason = $this->route('exit_reason');

                if (! $reason || $this->input('name') === $reason->name) {
                    return;
                }

                $count = $reason->studentsCount();
                if ($count > 0) {
                    $validator->errors()->add(
                        'name',
                        "This reason is recorded for {$count} ".str('student')->plural($count).'. Deactivate it and add a new reason instead.'
                    );
                }
            },
        ];
    }
}
