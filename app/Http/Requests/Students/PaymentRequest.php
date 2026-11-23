<?php

namespace App\Http\Requests\Students;

use App\Models\Fee;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PaymentRequest extends FormRequest
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
            'method' => ['required', Rule::in(Payment::METHODS)],
            'reference' => ['nullable', 'string', 'max:100'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var Fee $fee */
                $fee = $this->route('fee');
                if ($validator->errors()->has('amount')) {
                    return;
                }

                // Guard against typos: never pay more than is still owed for the term.
                $room = $fee->remainingToRecord();
                if ((float) $this->input('amount') > $room) {
                    $validator->errors()->add(
                        'amount',
                        $room > 0
                            ? 'Only '.number_format($room, 2).' is still owed for this term.'
                            : 'Nothing more is owed for this term.',
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
            'paid_on.before_or_equal' => 'The payment date can\'t be in the future.',
            'receipt.mimes' => 'Upload the receipt as a PDF or a photo (JPG, PNG or WebP).',
            'receipt.max' => 'The receipt must be 10 MB or smaller.',
        ];
    }
}
