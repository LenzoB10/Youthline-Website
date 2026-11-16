<?php

namespace App\Services\Students\Promotion;

use App\Enums\PromotionDecision;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Write side of promotion: applies the decisions the user made.
 * Adds ONE new enrolment per decided student for the current year — earlier years
 * are never changed, and running it twice can't create duplicates.
 */
class PromotionExecutor
{
    public function __construct(protected PromotionPlanner $planner) {}

    /**
     * @param  array<int, PromotionDecision>  $decisions  keyed by student id
     */
    public function apply(array $decisions): PromotionResult
    {
        $year = $this->planner->year() ?? throw new RuntimeException('Set the current academic year before promoting students.');
        $candidates = $this->planner->candidates($year);

        // Check everything first so a bad decision can't leave a half-done promotion.
        foreach ($decisions as $studentId => $decision) {
            $candidate = $candidates->get($studentId);
            if (! $candidate || ! $candidate->allows($decision)) {
                throw new InvalidArgumentException("Student {$studentId} can't be given the decision \"{$decision->value}\".");
            }
        }

        $promoted = $repeating = 0;

        DB::transaction(function () use ($decisions, $candidates, $year, &$promoted, &$repeating) {
            foreach ($decisions as $studentId => $decision) {
                $candidate = $candidates->get($studentId);

                Enrollment::query()->firstOrCreate(
                    ['student_id' => $studentId, 'academic_year_id' => $year->id],
                    [
                        'institution_id' => $candidate->institutionId,
                        'course' => $candidate->course,
                        'level' => $candidate->levelFor($decision)?->value,
                        'is_repeating' => $decision === PromotionDecision::Repeat,
                    ],
                );

                $decision === PromotionDecision::Promote ? $promoted++ : $repeating++;
            }
        });

        return new PromotionResult($promoted, $repeating);
    }
}
