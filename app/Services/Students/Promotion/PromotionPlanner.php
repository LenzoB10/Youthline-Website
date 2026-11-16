<?php

namespace App\Services\Students\Promotion;

use App\Contracts\Students\LevelProgression;
use App\Enums\EducationLevel;
use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\Settings\AcademicCalendarService;
use Illuminate\Support\Collection;

/**
 * Read side of promotion: who is waiting to be placed in the current year,
 * and what they could become. It never writes anything.
 */
class PromotionPlanner
{
    public function __construct(
        protected AcademicCalendarService $calendar,
        protected LevelProgression $progression,
    ) {}

    public function year(): ?AcademicYear
    {
        return $this->calendar->current();
    }

    /**
     * Active students with no enrolment for the given year yet, keyed by student id.
     *
     * @return Collection<int, PromotionCandidate>
     */
    public function candidates(?AcademicYear $year = null): Collection
    {
        $year ??= $this->year();
        if (! $year) {
            return collect();
        }

        return Student::query()
            ->where('status', StudentStatus::Active)
            ->whereDoesntHave('enrollments', fn ($q) => $q->where('academic_year_id', $year->id))
            ->with(['enrollments.academicYear', 'enrollments.institution'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn (Student $student) => [$student->id => $this->candidateFor($student)]);
    }

    /**
     * Grouped for the screen:
     *  - ready:    can move up a level, or repeat
     *  - decide:   top of a stage — can repeat here; otherwise decided on the profile
     *  - unplaced: no level on record, entered by hand on the profile
     *
     * @return array{year: ?AcademicYear, ready: array<int, array<string, mixed>>, decide: array<int, array<string, mixed>>, unplaced: array<int, array<string, mixed>>}
     */
    public function plan(): array
    {
        $year = $this->year();
        $candidates = $this->candidates($year);

        return [
            'year' => $year,
            'ready' => $candidates->filter->canPromote()->map->toArray()->values()->all(),
            'decide' => $candidates->filter(fn (PromotionCandidate $c) => $c->isPlaced() && ! $c->canPromote())->map->toArray()->values()->all(),
            'unplaced' => $candidates->reject->isPlaced()->map->toArray()->values()->all(),
        ];
    }

    protected function candidateFor(Student $student): PromotionCandidate
    {
        $previous = $this->latestEnrollment($student);
        $level = EducationLevel::tryFrom((string) $previous?->level);

        return new PromotionCandidate(
            studentId: $student->id,
            name: $student->full_name,
            fromLevel: $level,
            toLevel: $level ? $this->progression->next($level) : null,
            fromYear: $previous?->academicYear?->name,
            institutionId: $previous?->institution_id,
            institution: $previous?->institution?->name,
            course: $previous?->course,
        );
    }

    protected function latestEnrollment(Student $student): ?Enrollment
    {
        return $student->enrollments
            ->sortByDesc(fn (Enrollment $e) => sprintf(
                '%s|%s|%010d',
                $e->academicYear?->start_date?->format('Y-m-d') ?? '0000-00-00',
                $e->academicYear?->name ?? '',
                $e->id,
            ))
            ->first();
    }
}
