<?php

use App\Enums\FeeApproval;
use App\Enums\RepairStatus;
use App\Models\Business;
use App\Models\Consent;
use App\Models\Distribution;
use App\Models\HomeVisit;
use App\Models\Household;
use App\Models\ItemCategory;
use App\Models\Mentor;
use App\Models\MentorshipSession;
use App\Models\Photo;
use App\Models\RepairProject;
use App\Models\User;
use App\Notifications\RepairAwaitingApproval;
use App\Services\Settings\SystemSettingsService;
use App\Services\Support\BasicNeedsService;
use App\Services\Support\BusinessService;
use App\Services\Support\HomeVisitService;
use App\Services\Support\MentorshipService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

require_once __DIR__.'/../Students/helpers.php';

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
    ['admin' => $this->admin, 'manager' => $this->manager, 'outsider' => $this->outsider] = studentActors();
    $this->otherManager = User::factory()->create();
    $this->otherManager->assignRole('Manager');

    $this->household = Household::create(['head_name' => 'Banda', 'district' => 'Zomba']);
    $this->student = makeLearner(attributes: ['household_id' => $this->household->id]);
    $this->books = ItemCategory::create(['name' => 'Exercise books', 'unit_cost' => 300]);
});

// --- Basic needs -------------------------------------------------------------

it('records money for basic needs per student and as a group handout, with totals', function () {
    $second = makeLearner(attributes: ['first_name' => 'Kondwani']);
    $today = now()->toDateString();

    actingAs($this->manager)->post(route('students.distributions.store', $this->student), [
        'given_on' => $today, 'amount' => 25000,
    ])->assertSessionHasNoErrors();

    actingAs($this->manager)->post(route('support.basic-needs.group'), [
        'given_on' => $today, 'amount' => 20000, 'note' => 'Groceries',
        'student_ids' => [$this->student->id, $second->id],
    ])->assertSessionHas('success', 'Recorded for 2 students.');

    expect(Distribution::query()->count())->toBe(3)
        ->and(Distribution::query()->where('note', 'Groceries')->count())->toBe(2);

    $totals = app(BasicNeedsService::class)->totals((int) now()->format('Y'));
    expect($totals['total'])->toBe(65000.0)
        ->and($totals['students'])->toBe(2)
        ->and($totals['records'])->toBe(3)
        ->and($totals['months'][0]['total'])->toBe(65000.0);

    actingAs($this->manager)->post(route('students.distributions.store', $this->student), ['given_on' => $today, 'amount' => 0])
        ->assertSessionHasErrors('amount');
    actingAs($this->manager)->post(route('support.basic-needs.group'), [
        'given_on' => $today, 'amount' => 1000, 'student_ids' => [],
    ])->assertSessionHasErrors('student_ids');

    // Only the Admin deletes.
    $entry = Distribution::query()->first();
    actingAs($this->manager)->delete(route('students.distributions.destroy', [$entry->student_id, $entry]))->assertForbidden();
    actingAs($this->admin)->delete(route('students.distributions.destroy', [$entry->student_id, $entry]))->assertSessionHas('success');
});

it('still counts older item records at their unit cost', function () {
    Distribution::create(['student_id' => $this->student->id, 'item_category_id' => $this->books->id, 'quantity' => 6, 'given_on' => now()]);
    Distribution::create(['student_id' => $this->student->id, 'amount' => 10000, 'quantity' => 1, 'given_on' => now()]);

    expect(app(BasicNeedsService::class)->totals()['total'])->toBe(11800.0) // 6 × 300 + 10,000
        ->and(Distribution::query()->first()->describe('MWK'))->toBe('6 Exercise books');
});

// --- Mentorship --------------------------------------------------------------

it('keeps session notes private to their author and the Admin', function () {
    $mentor = Mentor::create(['name' => 'Grace Phiri']);
    actingAs($this->manager)->put(route('students.mentor.assign', $this->student), ['mentor_id' => $mentor->id])->assertSessionHasNoErrors();

    actingAs($this->manager)->post(route('students.sessions.store', $this->student), [
        'session_date' => now()->toDateString(), 'type' => 'counselling', 'notes' => 'Private: grief at home.', 'follow_up_needed' => true,
    ])->assertSessionHasNoErrors();
    $session = MentorshipSession::query()->sole();
    expect($session->mentor_id)->toBe($mentor->id); // defaults to the assigned mentor

    $notesSeenBy = fn (User $user) => actingAs($user)->get(route('students.show', $this->student))
        ->viewData('page')['props']['support']['mentorship']['sessions'][0]['notes'];

    expect($notesSeenBy($this->manager))->toBe('Private: grief at home.')
        ->and($notesSeenBy($this->admin))->toBe('Private: grief at home.')
        ->and($notesSeenBy($this->otherManager))->toBeNull();

    // Another manager can't edit it either.
    actingAs($this->otherManager)->put(route('students.sessions.update', [$this->student, $session]), [
        'session_date' => now()->toDateString(), 'type' => 'counselling', 'notes' => 'changed',
    ])->assertForbidden();

    // When the Admin shares notes with all managers, the other manager can read them.
    app(SystemSettingsService::class)->update(['counselling_notes_visibility' => 'all_managers'], $this->admin);
    expect($notesSeenBy($this->otherManager))->toBe('Private: grief at home.');
});

it('flags overdue mentorship and follow-ups', function () {
    $mentor = Mentor::create(['name' => 'Grace Phiri']);
    app(MentorshipService::class)->assign($this->student, $mentor->id, now()->subMonths(4)->toDateString());
    MentorshipSession::create(['student_id' => $this->student->id, 'mentor_id' => $mentor->id, 'session_date' => now()->subDays(70), 'follow_up_needed' => true]);

    $service = app(MentorshipService::class);
    expect($service->overdueStudents()->pluck('student_id')->all())->toBe([$this->student->id])
        ->and($service->followUpsNeeded()->pluck('student_id')->all())->toBe([$this->student->id]);

    MentorshipSession::create(['student_id' => $this->student->id, 'mentor_id' => $mentor->id, 'session_date' => now()->subDays(3)]);
    expect($service->overdueStudents())->toBeEmpty()->and($service->followUpsNeeded())->toBeEmpty();
});

it('changes mentors without losing the history', function () {
    [$a, $b] = [Mentor::create(['name' => 'A']), Mentor::create(['name' => 'B'])];
    $service = app(MentorshipService::class);
    $service->assign($this->student, $a->id);
    $service->assign($this->student, $b->id);

    expect($this->student->mentorAssignments()->count())->toBe(2)
        ->and($this->student->fresh()->currentMentorAssignment->mentor_id)->toBe($b->id);

    actingAs($this->admin)->delete(route('support.mentors.destroy', $a))->assertSessionHas('error'); // in use
    expect(Mentor::query()->count())->toBe(2);
});

// --- Home visits -------------------------------------------------------------

it('records a visit with a default next visit date and updates the household size', function () {
    actingAs($this->manager)->post(route('students.visits.store', $this->student), [
        'visit_date' => '2026-03-10', 'members_count' => 6, 'needs_identified' => 'Roof leaks',
    ])->assertSessionHasNoErrors();

    $visit = HomeVisit::query()->sole();
    expect($visit->next_visit_due->toDateString())->toBe('2027-03-10') // 12 months by default
        ->and($this->household->fresh()->members_count)->toBe(6);
});

it('only accepts visit photos when a guardian gave photo consent, and keeps them private', function () {
    $photo = fn () => UploadedFile::fake()->image('house.jpg');

    actingAs($this->manager)->post(route('students.visits.store', $this->student), [
        'visit_date' => now()->toDateString(), 'photos' => [$photo()],
    ])->assertSessionHasErrors('photos');

    Consent::create(['student_id' => $this->student->id, 'photo_allowed' => true]);
    actingAs($this->manager)->post(route('students.visits.store', $this->student), [
        'visit_date' => now()->toDateString(), 'photos' => [$photo()],
    ])->assertSessionHasNoErrors();

    $stored = Photo::query()->sole();
    Storage::disk('local')->assertExists($stored->file_path);
    actingAs($this->manager)->get(route('support.photos.show', $stored))->assertOk();
    actingAs($this->outsider)->get(route('support.photos.show', $stored))->assertForbidden();
});

it('won\'t touch a visit through another student\'s profile', function () {
    $visit = HomeVisit::create(['household_id' => $this->household->id, 'visit_date' => now()]);
    $stranger = makeLearner(attributes: ['first_name' => 'Other', 'household_id' => Household::create([])->id]);

    actingAs($this->admin)->delete(route('students.visits.destroy', [$stranger, $visit]))->assertNotFound();
    actingAs($this->admin)->delete(route('students.visits.destroy', [$this->student, $visit]))->assertSessionHas('success');
});

it('lists families due a visit', function () {
    $due = app(HomeVisitService::class)->dueHouseholds();
    expect($due->pluck('household_id')->all())->toBe([$this->household->id]); // never visited

    HomeVisit::create(['household_id' => $this->household->id, 'visit_date' => now(), 'next_visit_due' => now()->addYear()]);
    expect(app(HomeVisitService::class)->dueHouseholds())->toBeEmpty();
});

// --- Repairs -----------------------------------------------------------------

it('holds large repair budgets for the Admin, then moves Planned → In progress → Completed', function () {
    actingAs($this->manager)->post(route('students.repairs.store', $this->student), [
        'description' => 'New roof', 'estimated_cost' => 1500000, 'photos' => [UploadedFile::fake()->image('before.jpg')],
    ])->assertSessionHasNoErrors();

    $repair = RepairProject::query()->sole();
    expect($repair->approval_status)->toBe(FeeApproval::Pending)
        ->and($repair->photos()->value('kind'))->toBe('before');
    Notification::assertSentTo($this->admin, RepairAwaitingApproval::class);

    // No work before approval.
    actingAs($this->manager)->post(route('students.repairs.advance', [$this->student, $repair]))->assertSessionHasErrors('status');

    actingAs($this->manager)->put(route('finance.approvals.repairs.approve', $repair))->assertForbidden();
    actingAs($this->admin)->put(route('finance.approvals.repairs.approve', $repair))->assertSessionHas('success');

    actingAs($this->manager)->post(route('students.repairs.advance', [$this->student, $repair]), ['date' => '2026-04-01'])->assertSessionHasNoErrors();
    expect($repair->fresh()->status)->toBe(RepairStatus::InProgress);

    actingAs($this->manager)->post(route('students.repairs.advance', [$this->student, $repair]))->assertSessionHasErrors('actual_cost');
    actingAs($this->manager)->post(route('students.repairs.advance', [$this->student, $repair]), [
        'actual_cost' => 1450000, 'photos' => [UploadedFile::fake()->image('after.jpg')],
    ])->assertSessionHasNoErrors();

    $repair->refresh();
    expect($repair->status)->toBe(RepairStatus::Completed)
        ->and((float) $repair->actual_cost)->toBe(1450000.0)
        ->and($repair->photos()->where('kind', 'after')->count())->toBe(1);

    actingAs($this->manager)->post(route('students.repairs.advance', [$this->student, $repair]))->assertSessionHasErrors('status');
});

it('starts small repair budgets straight away', function () {
    actingAs($this->manager)->post(route('students.repairs.store', $this->student), ['description' => 'Door lock', 'estimated_cost' => 20000]);
    expect(RepairProject::query()->sole()->approval_status)->toBe(FeeApproval::Approved);
    Notification::assertNothingSent();
});

// --- Businesses --------------------------------------------------------------

it('tracks loan repayments and the remaining balance', function () {
    actingAs($this->manager)->post(route('students.businesses.store', $this->student), [
        'name' => 'Poultry', 'start_date' => '2026-01-10', 'amount_given' => 80000, 'funding_type' => 'loan',
    ])->assertSessionHasNoErrors();
    $business = Business::query()->sole();

    actingAs($this->manager);
    $repay = fn ($amount) => test()->post(route('students.businesses.repayments.store', [$this->student, $business]), ['amount' => $amount, 'paid_on' => now()->toDateString()]);

    $repay(30000)->assertSessionHasNoErrors();
    $repay(60000)->assertSessionHasErrors('amount'); // more than is left
    expect($business->fresh()->loanBalance())->toBe(50000.0);

    // Can't become a grant once repayments exist.
    actingAs($this->manager)->put(route('students.businesses.update', [$this->student, $business]), [
        'name' => 'Poultry', 'start_date' => '2026-01-10', 'amount_given' => 80000, 'funding_type' => 'grant',
    ])->assertSessionHasErrors('amount_given');
});

it('refuses repayments on a grant', function () {
    $grant = Business::create(['student_id' => $this->student->id, 'name' => 'Salon', 'start_date' => now(), 'amount_given' => 50000, 'funding_type' => 'grant']);

    actingAs($this->manager)->post(route('students.businesses.repayments.store', [$this->student, $grant]), ['amount' => 100, 'paid_on' => now()->toDateString()])
        ->assertSessionHasErrors('amount');
});

it('records check-ins, updates the running status and reminds when one is due', function () {
    $business = Business::create(['student_id' => $this->student->id, 'name' => 'Tailoring', 'start_date' => now()->subMonths(6), 'amount_given' => 50000, 'funding_type' => 'grant']);

    expect(app(BusinessService::class)->checkinsDue()->pluck('business_id')->all())->toBe([$business->id]);

    actingAs($this->manager)->post(route('students.businesses.checkins.store', [$this->student, $business]), [
        'checkin_date' => now()->toDateString(), 'is_running' => '1', 'monthly_income' => 30000, 'notes' => 'Doing well',
    ])->assertSessionHasNoErrors();
    expect(app(BusinessService::class)->checkinsDue())->toBeEmpty();

    actingAs($this->manager)->post(route('students.businesses.checkins.store', [$this->student, $business]), [
        'checkin_date' => now()->toDateString(), 'is_running' => '0', 'notes' => 'Closed after the rains',
    ]);
    expect($business->fresh()->is_running)->toBeFalse();
});

// --- Careers -----------------------------------------------------------------

it('records career interests, applications and alumni outcomes', function () {
    actingAs($this->manager)->put(route('students.interests.update', $this->student), ['interests' => ['Nursing', 'Teaching']])->assertSessionHasNoErrors();
    actingAs($this->manager)->put(route('students.interests.update', $this->student), ['interests' => ['Nursing']])->assertSessionHasNoErrors();
    expect($this->student->careerInterests()->pluck('interest')->all())->toBe(['Nursing']);

    actingAs($this->manager)->post(route('students.applications.store', $this->student), [
        'type' => 'university', 'organisation' => 'KUHES', 'course' => 'BSc Nursing', 'status' => 'applied',
    ])->assertSessionHasNoErrors();
    $application = $this->student->applications()->sole();

    actingAs($this->manager)->put(route('students.applications.update', [$this->student, $application]), [
        'type' => 'university', 'organisation' => 'KUHES', 'status' => 'accepted',
    ])->assertSessionHasNoErrors();
    expect($application->fresh()->status->value)->toBe('accepted');

    actingAs($this->manager)->post(route('students.outcomes.store', $this->student), [
        'recorded_on' => now()->toDateString(), 'outcome' => 'employed', 'details' => 'Nurse',
    ])->assertSessionHasNoErrors();

    actingAs($this->manager)->delete(route('students.applications.destroy', [$this->student, $application]))->assertForbidden();
});

// --- Access & pages ----------------------------------------------------------

it('keeps support records away from people without permission', function () {
    actingAs($this->outsider)->post(route('students.distributions.store', $this->student), [
        'given_on' => now()->toDateString(), 'amount' => 1000,
    ])->assertForbidden();
    actingAs($this->outsider)->get(route('support.mentors.index'))->assertForbidden();
    actingAs($this->outsider)->get(route('support.families'))->assertForbidden();
});

it('renders the support pages and the reminders on the dashboard', function (string $routeName) {
    actingAs($this->manager)->get(route($routeName))->assertOk();
})->with(['support.mentors.index', 'support.basic-needs.index', 'support.families', 'support.businesses']);

it('shows support reminders on the dashboard', function () {
    actingAs($this->manager)->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('reminders.visits', 1)
            ->where('reminders.visits.0.household', 'Banda')
            ->has('reminders.mentorship')
            ->has('reminders.checkins'));

    actingAs($this->outsider)->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('reminders.visits', null));
});
