<?php

use App\Models\Business;
use App\Models\Consent;
use App\Models\Distribution;
use App\Models\FamilySupport;
use App\Models\HomeVisit;
use App\Models\Household;
use App\Models\ItemCategory;
use App\Models\Mentor;
use App\Models\MentorshipSession;
use App\Models\Photo;
use App\Models\User;
use App\Services\Dashboard\OrganisationOverviewService;
use App\Services\Settings\SystemSettingsService;
use App\Services\Support\BasicNeedsService;
use App\Services\Support\BusinessService;
use App\Services\Support\HomeVisitService;
use App\Services\Support\MentorshipService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
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
        'given_on' => $today, 'amount' => 25000, 'note' => 'School shoes',
    ])->assertSessionHasNoErrors();
    expect(Distribution::query()->sole()->note)->toBe('School shoes');

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

it('gives no basic needs to graduates, while business support stays open to them', function () {
    $graduate = makeLearner('graduated', ['first_name' => 'Tadala']);
    $today = now()->toDateString();

    actingAs($this->manager)->post(route('students.distributions.store', $graduate), ['given_on' => $today, 'amount' => 20000])
        ->assertSessionHasErrors('amount');
    actingAs($this->manager)->post(route('support.basic-needs.group'), [
        'given_on' => $today, 'amount' => 20000, 'student_ids' => [$this->student->id, $graduate->id],
    ])->assertSessionHasErrors('student_ids.1');

    expect(Distribution::query()->count())->toBe(0)
        ->and($graduate->status->canReceiveBasicNeeds())->toBeFalse();

    // The profile tells the screen to hide the form.
    actingAs($this->manager)->get(route('students.show', $graduate))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('support.basicNeeds.canReceive', false));
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
    actingAs($this->manager)->post(route('support.families.visits.store', $this->household), [
        'visit_date' => '2026-03-10', 'members_count' => 6, 'needs_identified' => 'Roof leaks',
    ])->assertSessionHasNoErrors();

    $visit = HomeVisit::query()->sole();
    expect($visit->next_visit_due->toDateString())->toBe('2027-03-10') // 12 months by default
        ->and($this->household->fresh()->members_count)->toBe(6);
});

it('only accepts visit photos when a guardian gave photo consent, and keeps them private', function () {
    $photo = fn () => UploadedFile::fake()->image('house.jpg');

    actingAs($this->manager)->post(route('support.families.visits.store', $this->household), [
        'visit_date' => now()->toDateString(), 'photos' => [$photo()],
    ])->assertSessionHasErrors('photos');

    Consent::create(['student_id' => $this->student->id, 'photo_allowed' => true]);
    actingAs($this->manager)->post(route('support.families.visits.store', $this->household), [
        'visit_date' => now()->toDateString(), 'photos' => [$photo()],
    ])->assertSessionHasNoErrors();

    $stored = Photo::query()->sole();
    Storage::disk('local')->assertExists($stored->file_path);
    actingAs($this->manager)->get(route('support.photos.show', $stored))->assertOk();
    actingAs($this->outsider)->get(route('support.photos.show', $stored))->assertForbidden();
});

it('only touches a visit through its own family', function () {
    $visit = HomeVisit::create(['household_id' => $this->household->id, 'visit_date' => now()]);
    $otherFamily = Household::create(['head_name' => 'Other']);

    actingAs($this->admin)->delete(route('support.families.visits.destroy', [$otherFamily, $visit]))->assertNotFound();
    actingAs($this->admin)->delete(route('support.families.visits.destroy', [$this->household, $visit]))->assertSessionHas('success');
});

it('adds a community family with no student and supports it like any other', function () {
    actingAs($this->manager)->post(route('support.families.store'), [
        'head_name' => 'Esnart Phiri', 'district' => 'Zomba', 'members_count' => 7, 'notes' => 'Widow caring for orphans',
    ])->assertSessionHasNoErrors();

    $family = Household::query()->where('head_name', 'Esnart Phiri')->sole();
    expect($family->is_supported)->toBeTrue()
        ->and($family->created_by)->toBe($this->manager->id)
        ->and($family->students()->count())->toBe(0);

    // It gets visit reminders even with no student.
    expect(app(HomeVisitService::class)->dueHouseholds()->pluck('household_id'))->toContain($family->id);

    // Visits and support work the same way.
    actingAs($this->manager)->post(route('support.families.visits.store', $family), ['visit_date' => now()->toDateString()])->assertSessionHasNoErrors();
    actingAs($this->manager)->post(route('support.families.support.store', $family), [
        'given_on' => now()->toDateString(), 'type' => 'food', 'description' => 'Maize and cooking oil', 'amount' => 45000,
    ])->assertSessionHasNoErrors();
    expect($family->homeVisits()->count())->toBe(1)->and($family->familySupports()->count())->toBe(1);

    actingAs($this->manager)->get(route('support.families.show', $family))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Support/Household')
            ->where('household.name', 'Esnart Phiri')
            ->has('household.students', 0)
            ->has('visits', 1)
            ->has('support', 1)
            ->where('supportTotal', 45000));
});

it('lets a family give its own photo consent', function () {
    $family = Household::create(['head_name' => 'Community family']);
    $photo = fn () => UploadedFile::fake()->image('home.jpg');

    actingAs($this->manager)->post(route('support.families.visits.store', $family), ['visit_date' => now()->toDateString(), 'photos' => [$photo()]])
        ->assertSessionHasErrors('photos');

    actingAs($this->manager)->put(route('support.families.consent', $family), ['photo_consent' => true])->assertSessionHasErrors('photo_consent_on');
    actingAs($this->manager)->put(route('support.families.consent', $family), ['photo_consent' => true, 'photo_consent_on' => now()->toDateString()])
        ->assertSessionHasNoErrors();

    actingAs($this->manager)->post(route('support.families.visits.store', $family), ['visit_date' => now()->toDateString(), 'photos' => [$photo()]])
        ->assertSessionHasNoErrors();
    expect(Photo::query()->count())->toBe(1);
});

it('stops visit reminders when a family is no longer supported, keeping its history', function () {
    HomeVisit::create(['household_id' => $this->household->id, 'visit_date' => now()->subYears(2), 'next_visit_due' => now()->subYear()]);
    expect(app(HomeVisitService::class)->dueHouseholds()->pluck('household_id'))->toContain($this->household->id);

    actingAs($this->manager)->put(route('support.families.update', $this->household), ['head_name' => 'Banda', 'is_supported' => false])
        ->assertSessionHasNoErrors();

    expect(app(HomeVisitService::class)->dueHouseholds())->toBeEmpty()
        ->and($this->household->homeVisits()->count())->toBe(1);
});

it('shows a summary of the family on the student profile', function () {
    HomeVisit::create(['household_id' => $this->household->id, 'visit_date' => '2026-03-10', 'next_visit_due' => '2027-03-10', 'needs_identified' => 'Roof']);

    actingAs($this->manager)->get(route('students.show', $this->student))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('support.home.household.id', $this->household->id)
            ->where('support.home.lastVisit', '2026-03-10')
            ->where('support.home.visits', 1)
            ->where('support.home.needsIdentified', 'Roof'));
});

it('lists families due a visit', function () {
    $due = app(HomeVisitService::class)->dueHouseholds();
    expect($due->pluck('household_id')->all())->toBe([$this->household->id]); // never visited

    HomeVisit::create(['household_id' => $this->household->id, 'visit_date' => now(), 'next_visit_due' => now()->addYear()]);
    expect(app(HomeVisitService::class)->dueHouseholds())->toBeEmpty();
});

// --- Family support ----------------------------------------------------------

it('records the support given to a family, with type, amount and photos', function () {
    $this->household->update(['photo_consent' => true, 'photo_consent_on' => now()->toDateString()]);

    actingAs($this->manager)->post(route('support.families.support.store', $this->household), [
        'given_on' => '2026-03-02', 'type' => 'home_repair', 'description' => 'Roof sheets replaced', 'amount' => 300000,
        'note' => 'Rainy season leak', 'photos' => [UploadedFile::fake()->image('roof.jpg')],
    ])->assertSessionHas('success', 'Support recorded.');
    // Amount is optional (e.g. donated items).
    actingAs($this->manager)->post(route('support.families.support.store', $this->household), [
        'given_on' => now()->toDateString(), 'type' => 'household_items', 'description' => 'Blankets',
    ])->assertSessionHasNoErrors();

    $roof = FamilySupport::query()->where('type', 'home_repair')->sole();
    expect($roof->amount)->toBe('300000.00')
        ->and($roof->created_by)->toBe($this->manager->id)
        ->and($roof->photos()->count())->toBe(1)
        ->and(FamilySupport::query()->count())->toBe(2);

    // Validation: a type from the list, something described, no future dates.
    actingAs($this->manager)->post(route('support.families.support.store', $this->household), [
        'given_on' => now()->addDay()->toDateString(), 'type' => 'car', 'description' => '',
    ])->assertSessionHasErrors(['given_on', 'type', 'description']);

    // Only the Admin deletes, and only through the entry's own family.
    $other = Household::create(['head_name' => 'Other']);
    actingAs($this->manager)->delete(route('support.families.support.destroy', [$this->household, $roof]))->assertForbidden();
    actingAs($this->admin)->delete(route('support.families.support.destroy', [$other, $roof]))->assertNotFound();
    actingAs($this->admin)->delete(route('support.families.support.destroy', [$this->household, $roof]))->assertSessionHas('success');
    expect(FamilySupport::query()->count())->toBe(1);
    Storage::disk('local')->assertDirectoryEmpty('photos/family_support/'.$roof->id);
});

it('refuses support photos without the family\'s photo consent', function () {
    actingAs($this->manager)->post(route('support.families.support.store', $this->household), [
        'given_on' => now()->toDateString(), 'type' => 'food', 'description' => 'Maize',
        'photos' => [UploadedFile::fake()->image('maize.jpg')],
    ])->assertSessionHasErrors('photos');

    expect(FamilySupport::query()->count())->toBe(0);
});

it('lists recent family support on the Families page and in spending', function () {
    FamilySupport::create(['household_id' => $this->household->id, 'given_on' => now()->toDateString(), 'type' => 'money', 'description' => 'Rent', 'amount' => 60000]);

    actingAs($this->manager)->get(route('support.families'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('recentSupport', 1)
            ->where('supportTotals.given', 60000)
            ->where('supportTotals.families', 1));

    expect(collect(app(OrganisationOverviewService::class)->monthlySupport(1))->first()['family_support'])->toBe(60000.0);
});

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

it('records what graduates do afterwards, and only the Admin deletes it', function () {
    actingAs($this->manager)->post(route('students.outcomes.store', $this->student), [
        'recorded_on' => now()->toDateString(), 'outcome' => 'employed', 'details' => 'Nurse',
    ])->assertSessionHasNoErrors();
    $outcome = $this->student->alumniOutcomes()->sole();

    actingAs($this->manager)->delete(route('students.outcomes.destroy', [$this->student, $outcome]))->assertForbidden();
    actingAs($this->admin)->delete(route('students.outcomes.destroy', [$this->student, $outcome]))->assertSessionHas('success');
});

it('no longer has career interests or applications', function () {
    expect(Route::has('students.interests.update'))->toBeFalse()
        ->and(Route::has('students.applications.store'))->toBeFalse();
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
