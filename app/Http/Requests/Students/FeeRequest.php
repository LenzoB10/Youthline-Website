<?php

namespace App\Http\Requests\Students;

use App\Models\Fee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FeeRequest extends FormRequest
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
        $fee = $this->route('fee');

        return [
            'term_id' => [
                'required', 'integer', 'exists:terms,id',
                // One fee record per student per term.
                Rule::unique('fees', 'term_id')
                    ->where('student_id', $this->route('student')->id)
                    ->ignore($fee instanceof Fee ? $fee->id : null),
            ],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'amount_owed' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'youthline_share' => ['required', 'numeric', 'min:0', 'lte:amount_owed'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $fee = $this->route('fee');
                if (! $fee instanceof Fee || $validator->errors()->isNotEmpty()) {
                    return;
                }

                // Youthline's share can't drop below what has already been paid.
                $paid = $fee->paidTotal();
                if ((float) $this->input('youthline_share') < $paid) {
                    $validator->errors()->add(
                        'youthline_share',
                        'Youthline has already paid '.number_format($paid, 2).' for this term, so its share can\'t be lower than that.',
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'term_id.unique' => 'Fees for this term are already recorded. Edit that entry instead.',
            'youthline_share.lte' => 'Youthline\'s share can\'t be more than the amount owed.',
        ];
    }
}
