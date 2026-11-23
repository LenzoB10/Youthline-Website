<?php

namespace App\Services\Dashboard;

use App\Enums\EducationLevel;
use App\Enums\PaymentStatus;
use App\Enums\StudentStatus;
use App\Models\Business;
use App\Models\Distribution;
use App\Models\Enrollment;
use App\Models\FamilySupport;
use App\Models\Payment;
use App\Models\Program;
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
        'family-and-community-support' => 'family_support',
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
            'family_support' => (float) FamilySupport::query()->whereYear('given_on', $year)->sum('amount'),
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
     * Support money given per month over the last N months, split by where it went.
     *
     * @return array<int, array{month: string, label: string, fees: float, basic_needs: float, family_support: float, businesses: float}>
     */
    public function monthlySupport(int $months = 12): array
    {
        $from = now()->startOfMonth()->subMonths($months - 1);
        $key = fn ($date) => $date?->format('Y-m');

        $sums = [
            'fees' => Payment::query()->where('status', PaymentStatus::Paid->value)->where('paid_on', '>=', $from)->get(['paid_on', 'amount'])
                ->groupBy(fn (Payment $p) => $key($p->paid_on))->map(fn ($g) => $g->sum('amount')),
            'basic_needs' => Distribution::query()->with('itemCategory:id,unit_cost')->where('given_on', '>=', $from)->get()
                ->groupBy(fn (Distribution $d) => $key($d->given_on))->map(fn ($g) => $g->sum(fn (Distribution $d) => $d->value())),
            'family_support' => FamilySupport::query()->where('given_on', '>=', $from)->get(['given_on', 'amount'])
                ->groupBy(fn (FamilySupport $f) => $key($f->given_on))->map(fn ($g) => $g->sum('amount')),
            'businesses' => Business::query()->where('start_date', '>=', $from)->get(['start_date', 'amount_given'])
                ->groupBy(fn (Business $b) => $key($b->start_date))->map(fn ($g) => $g->sum('amount_given')),
        ];

        return collect(range(0, $months - 1))->map(function (int $i) use ($from, $sums) {
            $month = $from->copy()->addMonths($i);
            $ym = $month->format('Y-m');

            return [
                'month' => $ym,
                'label' => $month->format('M y'),
                'fees' => round((float) ($sums['fees'][$ym] ?? 0), 2),
                'basic_needs' => round((float) ($sums['basic_needs'][$ym] ?? 0), 2),
                'family_support' => round((float) ($sums['family_support'][$ym] ?? 0), 2),
                'businesses' => round((float) ($sums['businesses'][$ym] ?? 0), 2),
            ];
        })->all();
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
