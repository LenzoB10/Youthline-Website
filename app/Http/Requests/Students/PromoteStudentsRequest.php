<?php

namespace App\Http\Requests\Students;

use App\Enums\PromotionDecision;
use App\Services\Students\Promotion\PromotionPlanner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * decisions: { "<student id>": "promote" | "repeat" } — students left out are skipped.
 */
class PromoteStudentsRequest extends FormRequest
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
            'decisions' => ['required', 'array', 'min:1'],
            'decisions.*' => ['required', Rule::enum(PromotionDecision::class)],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $planner = app(PromotionPlanner::class);
                if (! $planner->year()) {
                    $validator->errors()->add('decisions', 'Set the current academic year in Settings before promoting students.');

                    return;
                }
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $candidates = $planner->candidates();
                foreach ($this->decisions() as $studentId => $decision) {
                    $candidate = $candidates->get($studentId);
                    if (! $candidate) {
                        $validator->errors()->add('decisions', 'One of the students is no longer waiting for promotion. Refresh the page and try again.');

                        return;
                    }
                    if (! $candidate->allows($decision)) {
                        $validator->errors()->add('decisions', "{$candidate->name} finished {$candidate->fromLevel?->label()}, so they can only repeat here. Record their next step on their profile.");

                        return;
                    }
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
            'decisions.required' => 'Choose Promote or Repeat for at least one student.',
            'decisions.min' => 'Choose Promote or Repeat for at least one student.',
        ];
    }

    /**
     * The validated decisions, keyed by student id.
     *
     * @return array<int, PromotionDecision>
     */
    public function decisions(): array
    {
        return collect($this->input('decisions', []))
            ->mapWithKeys(fn ($value, $id) => [(int) $id => PromotionDecision::tryFrom((string) $value)])
            ->filter()
            ->all();
    }
}
