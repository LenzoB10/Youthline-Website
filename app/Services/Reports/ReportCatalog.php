<?php

namespace App\Services\Reports;

use App\Enums\EducationLevel;
use App\Models\Business;
use App\Models\Student;
use App\Models\User;
use App\Services\Dashboard\OrganisationOverviewService;
use App\Services\Fees\BalanceService;
use App\Services\Settings\AcademicCalendarService;
use App\Services\Settings\SystemSettingsService;
use App\Services\Support\BasicNeedsService;
use App\Services\Website\ImpactMetricsService;

/**
 * Every exportable report: a title, headings and rows, built from live data.
 * Managers export the programme reports; the organisation summary is Admin-only.
 */
class ReportCatalog
{
    public function __construct(
        protected AcademicCalendarService $calendar,
        protected SystemSettingsService $settings,
        protected BalanceService $balances,
        protected BasicNeedsService $basicNeeds,
        protected ImpactMetricsService $impact,
        protected OrganisationOverviewService $overview,
    ) {}

    /**
     * @return array<string, array{title: string, description: string, admin_only: bool}>
     */
    public function definitions(): array
    {
        return [
            'students' => ['title' => 'Students', 'description' => 'Every student with status, level, school and programs this year.', 'admin_only' => false],
            'unpaid-fees' => ['title' => 'Unpaid fees', 'description' => 'Balances Youthline still owes, per student.', 'admin_only' => false],
            'basic-needs' => ['title' => 'Basic needs this year', 'description' => 'Money given to each student for basic needs this year.', 'admin_only' => false],
            'businesses' => ['title' => 'Youth businesses', 'description' => 'Businesses started, funding, loan balances and last check-in.', 'admin_only' => false],
            'impact' => ['title' => 'Impact summary', 'description' => 'The headline numbers shown on the website (counts only, no names).', 'admin_only' => false],
            'organisation' => ['title' => 'Organisation summary', 'description' => 'Students by status and level, and spending per program this year.', 'admin_only' => true],
        ];
    }

    /**
     * Report keys this user may export.
     *
     * @return array<int, string>
     */
    public function allowedFor(User $user): array
    {
        if (! $user->can('export reports')) {
            return [];
        }

        return collect($this->definitions())
            ->filter(fn ($def) => ! $def['admin_only'] || $user->can('view organisation overview'))
            ->keys()
            ->all();
    }

    /**
     * @return array{title: string, subtitle: string, headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public function build(string $key): array
    {
        $currency = $this->settings->get()->currency;
        $report = match ($key) {
            'students' => $this->students(),
            'unpaid-fees' => $this->unpaidFees($currency),
            'basic-needs' => $this->basicNeedsReport($currency),
            'businesses' => $this->businesses($currency),
            'impact' => $this->impactReport(),
            'organisation' => $this->organisation($currency),
        };

        return [
            'title' => $this->definitions()[$key]['title'],
            'subtitle' => 'Generated '.now()->format('j F Y'),
            ...$report,
        ];
    }

    /**
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    protected function students(): array
    {
        $yearId = $this->calendar->current()?->id;

        $rows = Student::query()
            ->with(['enrollments' => fn ($q) => $q->where('academic_year_id', $yearId)->with('institution'), 'activePrograms:programs.id,name'])
            ->orderBy('last_name')->orderBy('first_name')
            ->get()
            ->map(fn (Student $s) => [
                $s->full_name,
                $s->status->label(),
                EducationLevel::tryFrom((string) $s->enrollments->first()?->level)?->label() ?? '',
                $s->enrollments->first()?->institution?->name ?? '',
                $s->activePrograms->pluck('name')->join(', '),
                $s->guardian_name ?? '',
            ])->all();

        return ['headings' => ['Student', 'Status', 'Level', 'School', 'Programs', 'Guardian'], 'rows' => $rows];
    }

    protected function unpaidFees(string $currency): array
    {
        return [
            'headings' => ['Student', 'School(s)', 'Term(s)', "Youthline share ({$currency})", "Paid ({$currency})", "Balance ({$currency})"],
            'rows' => $this->balances->unpaidByStudent()->map(fn ($r) => [
                $r['student'], implode(', ', $r['schools']), implode(', ', $r['terms']),
                $r['share'], $r['paid'], $r['balance'],
            ])->all(),
        ];
    }

    protected function basicNeedsReport(string $currency): array
    {
        return [
            'headings' => ['Student', 'Times given', 'Last given', "Total ({$currency})"],
            'rows' => $this->basicNeeds->perStudent((int) now()->format('Y'))
                ->map(fn ($r) => [$r['student'], $r['times'], $r['last'] ?? '', $r['total']])->all(),
        ];
    }

    protected function businesses(string $currency): array
    {
        return [
            'headings' => ['Business', 'Owner', 'Started', 'Funding', "Given ({$currency})", "Loan balance ({$currency})", 'Running', 'Last check-in'],
            'rows' => Business::query()->with('student')
                ->withSum('loanRepayments as repaid_total', 'amount')
                ->withMax('checkins as last_checkin', 'checkin_date')
                ->orderBy('start_date')->get()
                ->map(fn (Business $b) => [
                    $b->name, $b->student?->full_name ?? '', $b->start_date?->toDateString() ?? '', $b->funding_type->label(),
                    (float) $b->amount_given, $b->loanBalance(), $b->is_running ? 'Yes' : 'No', $b->last_checkin ?? '',
                ])->all(),
        ];
    }

    protected function impactReport(): array
    {
        return [
            'headings' => ['Measure', 'Number'],
            'rows' => collect($this->impact->all())->map(fn ($m) => [$m['label'], $m['value']])->all(),
        ];
    }

    protected function organisation(string $currency): array
    {
        $rows = [];
        foreach ($this->overview->studentsByStatus() as $row) {
            $rows[] = ['Students: '.$row['label'], $row['count']];
        }
        foreach ($this->overview->studentsByLevel() as $row) {
            $rows[] = ['This year: '.$row['label'], $row['count']];
        }
        foreach ($this->overview->spendPerProgram() as $row) {
            $rows[] = ["Spent {$row['year']}: {$row['program']} ({$currency})", $row['amount'] ?? 'not tracked'];
        }

        return ['headings' => ['Measure', 'Value'], 'rows' => $rows];
    }
}
