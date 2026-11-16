<?php

namespace App\Http\Requests\Support;

use App\Enums\FundingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BusinessRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'business_type' => ['nullable', 'string', 'max:120'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'amount_given' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'funding_type' => ['required', Rule::enum(FundingType::class)],
        ];
    }
}
