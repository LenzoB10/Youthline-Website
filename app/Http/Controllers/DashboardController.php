<?php

namespace App\Http\Controllers;

use App\Enums\FeeApproval;
use App\Enums\StudentStatus;
use App\Models\Distribution;
use App\Models\Fee;
use App\Models\HomeVisit;
use App\Models\MentorshipSession;
use App\Models\Payment;
use App\Models\Student;
use App\Services\Academics\GradeTrendService;
use App\Services\Dashboard\OrganisationOverviewService;
use App\Services\Fees\BalanceService;
use App\Services\Settings\SystemSettingsService;
use App\Services\Support\BusinessService;
use App\Services\Support\HomeVisitService;
use App\Services\Support\MentorshipService;
use App\Services\Website\ImpactMetricsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "What needs attention" — each block only appears for people allowed to act on it.
 * (Development Plan Phase 5 will extend this into the full manager/admin dashboards.)
 */
class DashboardController extends Controller
{
    public function __construct(
        protected BalanceService $balances,
        protected GradeTrendService $trends,
        protected SystemSettingsService $settings,
        protected MentorshipService $mentorship,
        protected HomeVisitService $visits,
        protected BusinessService $businesses,
        protected OrganisationOverviewService $overview,
        protected ImpactMetricsService $impact,
    ) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'currency' => $this->settings->get()->currency,
            'students' => $user->can('list students') ? [
                'active' => Student::query()->where('status', StudentStatus::Active->value)->count(),
                'applicants' => Student::query()->where('status', StudentStatus::Applicant->value)->count(),
                'paused' => Student::query()->where('status', StudentStatus::Paused->value)->count(),
            ] : null,
            'fees' => $user->can('manage fees') ? $this->balances->totals() : null,
            'approvals' => $user->can('approve payments') ? [
                'scholarships' => Fee::query()->where('approval_status', FeeApproval::Pending->value)->count(),
            ] : null,
            'reminders' => [
                'mentorship' => $user->can('manage mentorship') ? $this->mentorship->overdueStudents()->take(8)->values() : null,
                'followUps' => $user->can('manage mentorship') ? $this->mentorship->followUpsNeeded()->take(8)->values() : null,
                'visits' => $user->can('manage home visits') ? $this->visits->dueHouseholds()->take(8)->values() : null,
                'checkins' => $user->can('manage businesses') ? $this->businesses->checkinsDue()->take(8)->values() : null,
            ],
            'reminderSettings' => [
                'mentorshipDays' => $this->settings->mentorshipOverdueDays(),
                'checkinDays' => $this->settings->businessCheckinDays(),
            ],
            'gradeDrops' => $user->can('record results') ? $this->trends->currentDrops(8) : null,
            'thisMonth' => $user->can('list students') ? $this->thisMonth() : null,
            'trend' => $user->can('manage fees') || $user->can('view organisation overview') ? $this->overview->monthlySupport(12) : null,
            'overview' => $user->can('view organisation overview') ? $this->organisation($request->integer('year') ?: null) : null,
            'gradeDropThreshold' => $this->settings->gradeDropThreshold(),
        ]);
    }

    /**
     * Quick totals of the work recorded this month.
     *
     * @return array<string, int>
     */
    protected function thisMonth(): array
    {
        $from = now()->startOfMonth();

        return [
            'sessions' => MentorshipSession::query()->where('session_date', '>=', $from)->count(),
            'basicNeeds' => round(Distribution::query()->with('itemCategory:id,unit_cost')->where('given_on', '>=', $from)->get()->sum(fn ($d) => $d->value()), 2),
            'visits' => HomeVisit::query()->where('visit_date', '>=', $from)->count(),
            'payments' => Payment::query()->where('status', 'paid')->where('paid_on', '>=', $from)->count(),
        ];
    }

    /**
     * The Admin's organisation-wide view.
     *
     * @return array<string, mixed>
     */
    protected function organisation(?int $year): array
    {
        $year ??= (int) now()->format('Y');
        $impact = collect($this->impact->all())->keyBy('key');

        return [
            'year' => $year,
            'byStatus' => $this->overview->studentsByStatus(),
            'byLevel' => $this->overview->studentsByLevel(),
            'spend' => $this->overview->spendPerProgram($year),
            'graduates' => $impact['graduates']['value'] ?? 0,
            'families' => $impact['families']['value'] ?? 0,
            'activity' => $this->overview->recentActivity(10),
        ];
    }
}
