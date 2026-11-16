<?php

namespace App\Services\Academics;

use App\Enums\ResultType;
use App\Models\AcademicResult;
use App\Notifications\GradeDropAlert;
use App\Services\Notifications\AlertRecipients;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Term-over-term comparison of a student's average (term and university results;
 * MSCE is points-based and stands alone).
 */
class GradeTrendService
{
    public function __construct(
        protected SystemSettingsService $settings,
        protected AlertRecipients $recipients,
    ) {}

    /**
     * The student's most recent averaged result from an earlier term.
     */
    public function previous(AcademicResult $result): ?AcademicResult
    {
        if (! $result->exam_type->usesAverage() || $result->average === null || ! $result->term?->start_date) {
            return null;
        }

        return AcademicResult::query()
            ->where('student_id', $result->student_id)
            ->whereKeyNot($result->getKey())
            ->whereIn('exam_type', [ResultType::Term->value, ResultType::University->value])
            ->whereNotNull('average')
            ->whereHas('term', fn ($q) => $q->where('start_date', '<', $result->term->start_date))
            ->with('term.academicYear')
            ->get()
            ->sortByDesc(fn (AcademicResult $r) => $r->term->start_date)
            ->first();
    }

    public function isDrop(?float $change): bool
    {
        return $change !== null && $change <= -$this->settings->gradeDropThreshold();
    }

    /**
     * Notify the people who record results when this result is a sharp drop.
     */
    public function alertIfDropped(AcademicResult $result): bool
    {
        $result->loadMissing('student', 'term.academicYear');
        $previous = $this->previous($result);

        if (! $previous || ! $this->isDrop((float) $result->average - (float) $previous->average)) {
            return false;
        }

        Notification::send(
            $this->recipients->withPermission('record results'),
            new GradeDropAlert($result, $previous),
        );

        return true;
    }

    /**
     * Annotate a student's results (any order) with change vs. the previous averaged
     * result and a drop flag. Returns them newest first.
     *
     * @param  Collection<int, AcademicResult>  $results  with term loaded
     * @return Collection<int, array{result: AcademicResult, change: ?float, is_drop: bool}>
     */
    public function annotate(Collection $results): Collection
    {
        $ordered = $results->sortBy(fn (AcademicResult $r) => [$r->term?->start_date?->timestamp ?? 0, $r->id])->values();
        $previousAverage = null;
        $rows = [];

        foreach ($ordered as $result) {
            $change = null;
            if ($result->exam_type->usesAverage() && $result->average !== null) {
                if ($previousAverage !== null) {
                    $change = round((float) $result->average - $previousAverage, 2);
                }
                $previousAverage = (float) $result->average;
            }

            $rows[] = ['result' => $result, 'change' => $change, 'is_drop' => $this->isDrop($change)];
        }

        return collect($rows)->reverse()->values();
    }

    /**
     * Students whose LATEST averaged result is a sharp drop — the dashboard list.
     *
     * @return Collection<int, array{student_id: int, student: string, from: float, to: float, change: float, term: string}>
     */
    public function currentDrops(int $limit = 10): Collection
    {
        return AcademicResult::query()
            ->whereIn('exam_type', [ResultType::Term->value, ResultType::University->value])
            ->whereNotNull('average')
            ->whereHas('term')
            ->with(['term.academicYear', 'student'])
            ->get()
            ->groupBy('student_id')
            ->map(function (Collection $results) {
                $sorted = $results->sortByDesc(fn (AcademicResult $r) => $r->term->start_date)->values();
                [$latest, $before] = [$sorted->get(0), $sorted->get(1)];
                if (! $before || ! $latest->student) {
                    return null;
                }
                $change = round((float) $latest->average - (float) $before->average, 2);

                return $this->isDrop($change) ? [
                    'student_id' => $latest->student_id,
                    'student' => $latest->student->full_name,
                    'from' => (float) $before->average,
                    'to' => (float) $latest->average,
                    'change' => $change,
                    'term' => trim(($latest->term->academicYear?->name ?? '').' '.$latest->term->name),
                ] : null;
            })
            ->filter()
            ->sortBy('change')
            ->take($limit)
            ->values();
    }
}
