<?php

namespace App\Services\Dashboard;

use App\Enums\EducationLevel;
use App\Enums\PaymentStatus;
use App\Enums\RepairStatus;
use App\Enums\StudentStatus;
use App\Models\Business;
use App\Models\Distribution;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Program;
use App\Models\RepairProject;
use App\Models\Student;
use App\Services\Settings\AcademicCalendarService;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * The Admin's organisation-wide view (Development Plan Phase 5).
 */
class OrganisationOverviewService
{
    /**
     * Where each program's money is recorded. Mentorship and career guidance have
     * no direct spend in the system, so they show as "not tracked".
     */
    protected const SPEND_SOURCES = [
        'educational-support-and-scholarships' => 'fees',
        'basic-needs-and-essential-support' => 'basic_needs',
        'family-and-community-support' => 'repairs',
        'youth-entrepreneurship-and-economic-empowerment' => 'businesses',
    ];

    public function __construct(protected AcademicCalendarService $calendar) {}

    /**
     * @return array<int, array{status: string, label: string, count: int}>
     */
    public function studentsByStatus(): array
    {
        $counts = Student::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(StudentStatus::cases())
            ->map(fn (StudentStatus $s) => ['status' => $s->value, 'label' => $s->label(), 'count' => (int) ($counts[$s->value] ?? 0)])
            ->all();
    }

    /**
     * Current-year enrolments of active students, per level.
     *
     * @return array<int, array{level: string, label: string, count: int}>
     */
    public function studentsByLevel(): array
    {
        $yearId = $this->calendar->current()?->id;
        if (! $yearId) {
            return [];
        }

        $counts = Enrollment::query()
            ->where('academic_year_id', $yearId)
            ->whereHas('student', fn ($q) => $q->where('status', StudentStatus::Active->value))
            ->selectRaw('level, count(*) as total')
            ->groupBy('level')
            ->pluck('total', 'level');

        return collect(EducationLevel::cases())
            ->map(fn (EducationLevel $l) => ['level' => $l->value, 'label' => $l->label(), 'count' => (int) ($counts[$l->value] ?? 0)])
            ->filter(fn ($row) => $row['count'] > 0)
            ->values()
            ->all();
    }

    /**
     * Money spent per program in a calendar year (null = not tracked).
     *
     * @return array<int, array{program: string, amount: ?float, year: int}>
     */
    public function spendPerProgram(?int $year = null): array
    {
        $year ??= (int) now()->format('Y');

        $amounts = [
            'fees' => (float) Payment::query()->where('status', PaymentStatus::Paid->value)->whereYear('paid_on', $year)->sum('amount'),
            'basic_needs' => (float) Distribution::query()->with('itemCategory:id,unit_cost')->whereYear('given_on', $year)->get()
                ->sum(fn (Distribution $d) => $d->value()),
            'repairs' => (float) RepairProject::query()->where('status', RepairStatus::Completed->value)->whereYear('completed_on', $year)->sum('actual_cost'),
            'businesses' => (float) Business::query()->whereYear('start_date', $year)->sum('amount_given'),
        ];

        return Program::query()->orderBy('display_order')->get(['name', 'slug'])
            ->map(fn (Program $p) => [
                'program' => $p->name,
                'amount' => isset(self::SPEND_SOURCES[$p->slug]) ? round($amounts[self::SPEND_SOURCES[$p->slug]], 2) : null,
                'year' => $year,
            ])->all();
    }

    /**
     * Latest audit-log entries in plain words.
     *
     * @return Collection<int, array{id: int, who: string, what: string, when: ?string}>
     */
    public function recentActivity(int $limit = 10): Collection
    {
        return Activity::query()->with('causer')->latest('id')->limit($limit)->get()
            ->map(fn (Activity $a) => [
                'id' => $a->id,
                'who' => $a->causer?->name ?? 'System',
                'what' => trim(ucfirst((string) $a->description).' '.str(class_basename((string) $a->subject_type))->headline()->lower()),
                'when' => $a->created_at?->toIso8601String(),
            ]);
    }
}
