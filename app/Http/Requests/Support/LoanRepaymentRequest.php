<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class LoanRepaymentRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
