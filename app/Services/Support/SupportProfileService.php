<?php

namespace App\Services\Support;

use App\Enums\AlumniOutcomeType;
use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\FundingType;
use App\Enums\SessionType;
use App\Models\Business;
use App\Models\ItemCategory;
use App\Models\Mentor;
use App\Models\MentorshipSession;
use App\Models\Photo;
use App\Models\RepairProject;
use App\Models\Student;
use App\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Carbon;

/**
 * Everything the student profile's support tabs show, shaped for the frontend.
 * Restricted session notes are only included for people allowed to read them.
 */
class SupportProfileService
{
    public function __construct(
        protected BasicNeedsService $basicNeeds,
        protected MentorshipService $mentorship,
        protected SystemSettingsService $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forStudent(Student $student, User $viewer): array
    {
        $student->load([
            'distributions' => fn ($q) => $q->with(['itemCategory:id,name,unit_cost', 'recorder:id,name'])->latest('given_on')->latest('id'),
            'currentMentorAssignment.mentor',
            'mentorshipSessions' => fn ($q) => $q->with(['mentor:id,name', 'recorder:id,name'])->latest('session_date')->latest('id'),
            'household.homeVisits' => fn ($q) => $q->with(['recorder:id,name', 'photos'])->latest('visit_date'),
            'household.repairProjects' => fn ($q) => $q->with(['photos', 'approver:id,name'])->latest('id'),
            'businesses' => fn ($q) => $q->withSum('loanRepayments as repaid_total', 'amount')
                ->with(['checkins' => fn ($c) => $c->with('recorder:id,name')->latest('checkin_date'), 'loanRepayments' => fn ($r) => $r->latest('paid_on')]),
            'careerInterests', 'applications' => fn ($q) => $q->latest('applied_on'), 'alumniOutcomes' => fn ($q) => $q->latest('recorded_on'),
        ]);

        return [
            'basicNeeds' => [
                'items' => $student->distributions->map(fn ($d) => [
                    'id' => $d->id,
                    'amount' => $d->amount !== null ? (float) $d->amount : null,
                    'what' => $d->describe($this->settings->get()->currency),
                    'note' => $d->note,
                    'given_on' => $d->given_on?->toDateString(),
                    'recorded_by' => $d->recorder?->name,
                ])->values(),
                'total' => round($student->distributions->sum(fn ($d) => $d->value()), 2),
                'thisYear' => $this->basicNeeds->studentTotal($student, now()->startOfYear()),
            ],
            'mentorship' => $this->mentorship($student, $viewer),
            'home' => $this->home($student),
            'businesses' => $student->businesses->map(fn (Business $b) => $this->business($b))->values(),
            'careers' => [
                'interests' => $student->careerInterests->pluck('interest')->values(),
                'applications' => $student->applications->map(fn ($a) => [
                    'id' => $a->id,
                    'type' => $a->type->value,
                    'type_label' => $a->type->label(),
                    'organisation' => $a->organisation,
                    'course' => $a->course,
                    'applied_on' => $a->applied_on?->toDateString(),
                    'status' => $a->status->value,
                    'status_label' => $a->status->label(),
                    'notes' => $a->notes,
                ])->values(),
                'outcomes' => $student->alumniOutcomes->map(fn ($o) => [
                    'id' => $o->id,
                    'recorded_on' => $o->recorded_on?->toDateString(),
                    'outcome' => $o->outcome->value,
                    'outcome_label' => $o->outcome->label(),
                    'details' => $o->details,
                ])->values(),
            ],
        ];
    }

    /**
     * Select options for the support forms.
     *
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'itemCategories' => ItemCategory::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['value' => $c->id, 'label' => $c->name]),
            'mentors' => Mentor::query()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($m) => ['value' => $m->id, 'label' => $m->name]),
            'sessionTypes' => SessionType::options(),
            'fundingTypes' => FundingType::options(),
            'applicationTypes' => ApplicationType::options(),
            'applicationStatuses' => ApplicationStatus::options(),
            'outcomeTypes' => AlumniOutcomeType::options(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mentorship(Student $student, User $viewer): array
    {
        $assignment = $student->currentMentorAssignment;

        return [
            'mentor' => $assignment?->mentor ? [
                'id' => $assignment->mentor->id,
                'name' => $assignment->mentor->name,
                'phone' => $assignment->mentor->phone,
                'since' => $assignment->start_date?->toDateString(),
            ] : null,
            'summary' => $this->mentorship->summary($student),
            'notesVisibleToAll' => $this->settings->sessionNotesVisibleToAllManagers(),
            'sessions' => $student->mentorshipSessions->map(function (MentorshipSession $s) use ($viewer) {
                $canRead = $this->mentorship->canReadNotes($viewer, $s);

                return [
                    'id' => $s->id,
                    'session_date' => $s->session_date?->toDateString(),
                    'type' => $s->type->value,
                    'type_label' => $s->type->label(),
                    'mentor_id' => $s->mentor_id,
                    'mentor' => $s->mentor?->name,
                    'follow_up_needed' => $s->follow_up_needed,
                    'notes' => $canRead ? $s->notes : null,
                    'notes_hidden' => ! $canRead && filled($s->notes),
                    'can_edit' => $canRead,
                    'recorded_by' => $s->recorder?->name,
                ];
            })->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function home(Student $student): array
    {
        $household = $student->household;
        if (! $household) {
            return ['household' => null];
        }

        $latest = $household->homeVisits->first();

        return [
            'household' => ['id' => $household->id, 'name' => $household->head_name ?: 'Unnamed household'],
            'photoConsent' => $household->photoConsentGiven(),
            'nextVisitDue' => $latest?->next_visit_due?->toDateString(),
            'visitOverdue' => ! $latest || ($latest->next_visit_due?->isPast() ?? false),
            'defaultNextVisitMonths' => $this->settings->homeVisitIntervalMonths(),
            'repairApprovalLimit' => $this->settings->get()->require_repair_approval
                ? (float) $this->settings->get()->repair_approval_limit : null,
            'visits' => $household->homeVisits->map(fn ($v) => [
                'id' => $v->id,
                'visit_date' => $v->visit_date?->toDateString(),
                'members_count' => $v->members_count,
                'living_conditions' => $v->living_conditions,
                'income_sources' => $v->income_sources,
                'needs_identified' => $v->needs_identified,
                'next_visit_due' => $v->next_visit_due?->toDateString(),
                'recorded_by' => $v->recorder?->name,
                'photos' => $v->photos->map(fn (Photo $p) => $this->photo($p))->values(),
            ])->values(),
            'repairs' => $household->repairProjects->map(fn (RepairProject $r) => [
                'id' => $r->id,
                'description' => $r->description,
                'estimated_cost' => $r->estimated_cost !== null ? (float) $r->estimated_cost : null,
                'actual_cost' => $r->actual_cost !== null ? (float) $r->actual_cost : null,
                'status' => $r->status->value,
                'status_label' => $r->status->label(),
                'next_status_label' => $r->status->next()?->label(),
                'approval_status' => $r->approval_status->value,
                'rejection_reason' => $r->rejection_reason,
                'approved_by' => $r->approver?->name,
                'started_on' => $r->started_on?->toDateString(),
                'completed_on' => $r->completed_on?->toDateString(),
                'photos' => $r->photos->map(fn (Photo $p) => $this->photo($p))->values(),
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function business(Business $b): array
    {
        $last = $b->checkins->first();
        $from = $last?->checkin_date ?? $b->start_date;

        return [
            'id' => $b->id,
            'name' => $b->name,
            'business_type' => $b->business_type,
            'start_date' => $b->start_date?->toDateString(),
            'amount_given' => (float) $b->amount_given,
            'funding_type' => $b->funding_type->value,
            'funding_label' => $b->funding_type->label(),
            'is_running' => $b->is_running,
            'repaid' => $b->repaidTotal(),
            'balance' => $b->loanBalance(),
            'last_checkin' => $last?->checkin_date?->toDateString(),
            'checkin_due' => $b->is_running && (! $from || Carbon::parse($from)->lt(now()->subDays($this->settings->businessCheckinDays())->startOfDay())),
            'checkins' => $b->checkins->map(fn ($c) => [
                'id' => $c->id,
                'checkin_date' => $c->checkin_date?->toDateString(),
                'is_running' => $c->is_running,
                'monthly_income' => $c->monthly_income !== null ? (float) $c->monthly_income : null,
                'notes' => $c->notes,
                'recorded_by' => $c->recorder?->name,
            ])->values(),
            'repayments' => $b->loanRepayments->map(fn ($r) => [
                'id' => $r->id,
                'amount' => (float) $r->amount,
                'paid_on' => $r->paid_on?->toDateString(),
            ])->values(),
        ];
    }

    /**
     * @return array{id: int, kind: string, url: string, name: ?string}
     */
    protected function photo(Photo $photo): array
    {
        return [
            'id' => $photo->id,
            'kind' => $photo->kind,
            'url' => route('support.photos.show', $photo),
            'name' => $photo->original_name,
        ];
    }
}
