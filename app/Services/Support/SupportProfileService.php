<?php

namespace App\Services\Support;

use App\Enums\AlumniOutcomeType;
use App\Enums\FamilySupportType;
use App\Enums\FundingType;
use App\Enums\SessionType;
use App\Models\Business;
use App\Models\FamilySupport;
use App\Models\Household;
use App\Models\ItemCategory;
use App\Models\Mentor;
use App\Models\MentorshipSession;
use App\Models\Photo;
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
            'household.latestVisit',
            'businesses' => fn ($q) => $q->withSum('loanRepayments as repaid_total', 'amount')
                ->with(['checkins' => fn ($c) => $c->with('recorder:id,name')->latest('checkin_date'), 'loanRepayments' => fn ($r) => $r->latest('paid_on')]),
            'alumniOutcomes' => fn ($q) => $q->latest('recorded_on'),
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
                'canReceive' => $student->status->canReceiveBasicNeeds(),
            ],
            'mentorship' => $this->mentorship($student, $viewer),
            'home' => $this->home($student),
            'businesses' => $student->businesses->map(fn (Business $b) => $this->business($b))->values(),
            'careers' => [
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
     * The student's family, as a summary — visits and support are recorded on the
     * family's own page (Support → Families).
     *
     * @return array<string, mixed>
     */
    protected function home(Student $student): array
    {
        $household = $student->household;
        if (! $household) {
            return ['household' => null];
        }

        $latest = $household->latestVisit;

        return [
            'household' => ['id' => $household->id, 'name' => $household->displayName()],
            'lastVisit' => $latest?->visit_date?->toDateString(),
            'nextVisitDue' => $latest?->next_visit_due?->toDateString(),
            'visitOverdue' => $household->is_supported && (! $latest || ($latest->next_visit_due?->isPast() ?? false)),
            'needsIdentified' => $latest?->needs_identified,
            'visits' => $household->homeVisits()->count(),
            'supportTotal' => round((float) $household->familySupports()->sum('amount'), 2),
            'lastSupport' => $household->familySupports()->max('given_on'),
            'isSupported' => $household->is_supported,
        ];
    }

    /**
     * A family's own page: details, consent, students, visits and the support given.
     *
     * @return array<string, mixed>
     */
    public function household(Household $household): array
    {
        $household->load([
            'students:id,household_id,first_name,last_name,status',
            'homeVisits' => fn ($q) => $q->with(['recorder:id,name', 'photos'])->latest('visit_date'),
            'familySupports' => fn ($q) => $q->with(['photos', 'creator:id,name'])->latest('given_on')->latest('id'),
        ]);
        $latest = $household->homeVisits->first();

        return [
            'household' => [
                'id' => $household->id,
                'name' => $household->displayName(),
                'head_name' => $household->head_name,
                'village_area' => $household->village_area,
                'district' => $household->district,
                'phone' => $household->phone,
                'members_count' => $household->members_count,
                'notes' => $household->notes,
                'is_supported' => $household->is_supported,
                'photo_consent' => $household->photo_consent,
                'photo_consent_on' => $household->photo_consent_on?->toDateString(),
                'students' => $household->students->map(fn (Student $s) => [
                    'id' => $s->id, 'name' => $s->full_name, 'status' => $s->status->label(),
                ])->values(),
            ],
            'photoConsent' => $household->photoConsentGiven(),
            'nextVisitDue' => $latest?->next_visit_due?->toDateString(),
            'visitOverdue' => ! $latest || ($latest->next_visit_due?->isPast() ?? false),
            'defaultNextVisitMonths' => $this->settings->homeVisitIntervalMonths(),
            'supportTypes' => FamilySupportType::options(),
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
            'support' => $household->familySupports->map(fn (FamilySupport $f) => [
                'id' => $f->id,
                'given_on' => $f->given_on?->toDateString(),
                'type' => $f->type->value,
                'type_label' => $f->type->label(),
                'description' => $f->description,
                'amount' => $f->amount !== null ? (float) $f->amount : null,
                'note' => $f->note,
                'recorded_by' => $f->creator?->name,
                'photos' => $f->photos->map(fn (Photo $p) => $this->photo($p))->values(),
            ])->values(),
            'supportTotal' => round((float) $household->familySupports->sum(fn (FamilySupport $f) => (float) $f->amount), 2),
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
