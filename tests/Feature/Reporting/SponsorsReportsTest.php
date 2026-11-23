<?php

use App\Enums\PaymentStatus;
use App\Models\AcademicResult;
use App\Models\MentorshipSession;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Sponsor;
use App\Models\Sponsorship;
use App\Notifications\DailyReminderDigest;
use App\Services\Dashboard\OrganisationOverviewService;
use App\Services\Reminders\ReminderDigestService;
use App\Services\Sponsors\SponsorReportService;
use Illuminate\Console\Scheduling\Schedule;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;

require_once __DIR__.'/../Academics/helpers.php';

beforeEach(function () {
    ['admin' => $this->admin, 'manager' => $this->manager, 'outsider' => $this->outsider] = studentActors();
    $this->year = makeYear('2026', current: true);
    $this->term = makeTerm($this->year, 'Term 1', '2026-01-06', current: true);
    $this->student = makeLearner();
    enrol($this->student, $this->year, 'form_3');
});

// --- Sponsors ----------------------------------------------------------------

it('lets managers keep sponsors and link students, and only the Admin delete them', function () {
    actingAs($this->manager)->post(route('sponsors.store'), ['name' => 'Rotary Club', 'type' => 'organisation'])
        ->assertRedirect();
    $sponsor = Sponsor::query()->sole();

    actingAs($this->manager)->post(route('sponsors.sponsorships.store', $sponsor), [
        'student_id' => $this->student->id, 'start_date' => '2026-01-01', 'amount_per_year' => 150000,
    ])->assertSessionHasNoErrors();
    actingAs($this->manager)->post(route('sponsors.sponsorships.store', $sponsor), [
        'student_id' => $this->student->id, 'start_date' => '2026-02-01',
    ])->assertSessionHasErrors('student_id'); // already sponsored by them

    actingAs($this->manager)->delete(route('sponsors.destroy', $sponsor))->assertForbidden();
    actingAs($this->outsider)->get(route('sponsors.index'))->assertForbidden();
    actingAs($this->admin)->delete(route('sponsors.destroy', $sponsor))->assertRedirect(route('sponsors.index'));
    expect(Sponsor::query()->count())->toBe(0);
});

it('builds the sponsor report from live data without private notes, and downloads it as a PDF', function () {
    $sponsor = Sponsor::create(['name' => 'Mary Johnson']);
    $sponsorship = Sponsorship::create(['sponsor_id' => $sponsor->id, 'student_id' => $this->student->id, 'start_date' => '2025-01-01']);
    $fee = makeFee($this->student, $this->term, 45000);
    Payment::create(['fee_id' => $fee->id, 'amount' => 20000, 'status' => PaymentStatus::Paid, 'paid_on' => '2026-01-20']);
    AcademicResult::create(['student_id' => $this->student->id, 'term_id' => $this->term->id, 'exam_type' => 'term', 'average' => 71.5]);
    MentorshipSession::create(['student_id' => $this->student->id, 'session_date' => now(), 'notes' => 'PRIVATE COUNSELLING NOTE']);

    actingAs($this->manager)->put(route('sponsors.sponsorships.progress', [$sponsor, $sponsorship]), [
        'progress_update' => 'Thandiwe is top of her class in English.',
    ])->assertSessionHasNoErrors();

    $report = app(SponsorReportService::class)->build($sponsor->fresh());
    $student = $report['students'][0];

    expect($student['level'])->toBe('Form 3')
        ->and($student['results'][0]['score'])->toBe('71.5%')
        ->and($student['fees'])->toBe(['share' => 45000.0, 'paid' => 20000.0])
        ->and($student['support']['sessions'])->toBe(1)
        ->and($student['update'])->toBe('Thandiwe is top of her class in English.')
        ->and(json_encode($report))->not->toContain('PRIVATE COUNSELLING NOTE');

    $response = actingAs($this->manager)->get(route('sponsors.report', $sponsor))->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

it('leaves ended sponsorships out of the report', function () {
    $sponsor = Sponsor::create(['name' => 'Past sponsor']);
    Sponsorship::create(['sponsor_id' => $sponsor->id, 'student_id' => $this->student->id, 'start_date' => '2024-01-01', 'end_date' => '2025-12-31']);

    expect(app(SponsorReportService::class)->build($sponsor)['students'])->toBe([]);
});

// --- Exports -----------------------------------------------------------------

it('exports reports as real Excel and PDF files', function (string $report) {
    $xlsx = actingAs($this->manager)->get(route('reports.export', ['report' => $report, 'format' => 'xlsx']))->assertOk();
    expect(substr($xlsx->streamedContent(), 0, 2))->toBe('PK'); // an .xlsx is a zip

    $pdf = actingAs($this->manager)->get(route('reports.export', ['report' => $report, 'format' => 'pdf']))->assertOk();
    expect(substr($pdf->getContent(), 0, 4))->toBe('%PDF');
})->with(['students', 'unpaid-fees', 'basic-needs', 'businesses', 'impact']);

it('keeps the organisation summary for the Admin', function () {
    actingAs($this->manager)->get(route('reports.export', ['report' => 'organisation', 'format' => 'pdf']))->assertForbidden();
    actingAs($this->admin)->get(route('reports.export', ['report' => 'organisation', 'format' => 'pdf']))->assertOk();
    actingAs($this->outsider)->get(route('reports.index'))->assertForbidden();

    actingAs($this->manager)->get(route('reports.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('reports', 5));
});

// --- Dashboards --------------------------------------------------------------

it('gives the Admin an organisation overview with spend per program', function () {
    $program = Program::create(['name' => 'Educational Support and Scholarships', 'slug' => 'educational-support-and-scholarships']);
    $fee = makeFee($this->student, $this->term, 90000);
    Payment::create(['fee_id' => $fee->id, 'amount' => 60000, 'status' => PaymentStatus::Paid, 'paid_on' => '2026-02-01']);
    Payment::create(['fee_id' => $fee->id, 'amount' => 30000, 'status' => PaymentStatus::Error, 'paid_on' => '2026-02-01']);

    $spend = collect(app(OrganisationOverviewService::class)->spendPerProgram(2026))->keyBy('program');
    expect($spend[$program->name]['amount'])->toBe(60000.0);

    actingAs($this->admin)->get(route('dashboard', ['year' => 2026]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('overview.year', 2026)
            ->where('overview.byLevel.0.label', 'Form 3')
            ->has('overview.activity')
            ->has('thisMonth'));

    actingAs($this->manager)->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('overview', null));
});

it('shows 12 months of support given to the Admin and the Manager, but not to others', function () {
    $fee = makeFee($this->student, $this->term, 90000);
    Payment::create(['fee_id' => $fee->id, 'amount' => 25000, 'status' => PaymentStatus::Paid, 'paid_on' => now()->startOfMonth()]);
    Payment::create(['fee_id' => $fee->id, 'amount' => 10000, 'status' => PaymentStatus::Paid, 'paid_on' => now()->subMonthNoOverflow()->startOfMonth()]);
    Payment::create(['fee_id' => $fee->id, 'amount' => 99000, 'status' => PaymentStatus::Paid, 'paid_on' => now()->subMonths(13)]);

    $months = app(OrganisationOverviewService::class)->monthlySupport(12);
    expect($months)->toHaveCount(12)
        ->and($months[11]['month'])->toBe(now()->format('Y-m'))
        ->and($months[11]['fees'])->toBe(25000.0)
        ->and($months[10]['fees'])->toBe(10000.0)
        ->and(collect($months)->sum('fees'))->toBe(35000.0); // older than 12 months is left out

    foreach ([$this->admin, $this->manager] as $user) {
        actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('trend', 12));
    }
    actingAs($this->outsider)->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('trend', null));
});

// --- Reminders engine --------------------------------------------------------

it('sends each person one daily digest of what is due, only for their own areas', function () {
    makeFee($this->student, $this->term, 45000); // unpaid → fees reminder

    $sent = app(ReminderDigestService::class)->sendAll();
    expect($sent)->toBe(2); // Admin + Manager, not the outsider

    $digest = $this->manager->notifications()->where('type', DailyReminderDigest::class)->sole();
    expect($digest->data['message'])->toContain('1 student has unpaid fees');
    expect($this->outsider->notifications()->count())->toBe(0);

    // Running it again the same day sends nothing new.
    artisan('youthline:send-reminders')->assertSuccessful();
    expect($this->manager->notifications()->where('type', DailyReminderDigest::class)->count())->toBe(1);
});

it('schedules the reminders every morning', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($e) => str_contains((string) $e->command, 'youthline:send-reminders'));

    expect($events)->toHaveCount(1)->and($events->first()->expression)->toBe('0 7 * * *');
});
