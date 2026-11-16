<?php

use App\Enums\FeeApproval;
use App\Enums\PaymentStatus;
use App\Models\Document;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\Payment;
use App\Notifications\ApprovalDecided;
use App\Notifications\PaymentAwaitingApproval;
use App\Notifications\ScholarshipAwaitingApproval;
use App\Services\Fees\BalanceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

require_once __DIR__.'/helpers.php';

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
    ['admin' => $this->admin, 'manager' => $this->manager, 'outsider' => $this->outsider] = studentActors();

    $this->year = makeYear('2025', current: true);
    $this->t1 = makeTerm($this->year, 'Term 1', '2025-01-06');
    $this->t2 = makeTerm($this->year, 'Term 2', '2025-04-28', current: true);
    $this->school = Institution::create(['name' => 'Zomba Secondary', 'type' => 'secondary']);
    $this->student = makeLearner();
    enrol($this->student, $this->year, 'form_2', ['institution_id' => $this->school->id]);
});

function pay(Fee $fee, array $data = [])
{
    return test()->post(route('students.payments.store', [$fee->student_id, $fee]), [
        'amount' => 20000, 'paid_on' => '2025-02-01', 'method' => 'Bank transfer', 'reference' => 'TX-1', ...$data,
    ]);
}

// --- Fees & scholarships -----------------------------------------------------

it('treats a student\'s first fees as a new scholarship needing Admin approval', function () {
    actingAs($this->manager)->post(route('students.fees.store', $this->student), [
        'term_id' => $this->t1->id, 'amount_owed' => 50000, 'youthline_share' => 45000,
    ])->assertSessionHasNoErrors();

    $fee = Fee::query()->sole();
    expect($fee->approval_status)->toBe(FeeApproval::Pending)
        ->and($fee->institution_id)->toBe($this->school->id); // defaulted from the enrolment
    Notification::assertSentTo($this->admin, ScholarshipAwaitingApproval::class);

    // No payment until it's approved.
    actingAs($this->manager);
    pay($fee)->assertSessionHasErrors('amount');

    actingAs($this->admin)->put(route('finance.approvals.scholarships.approve', $fee))->assertSessionHas('success');
    expect($fee->fresh()->approval_status)->toBe(FeeApproval::Approved);
    Notification::assertSentTo($this->manager, ApprovalDecided::class);

    // The next term is not a new scholarship.
    actingAs($this->manager)->post(route('students.fees.store', $this->student), [
        'term_id' => $this->t2->id, 'amount_owed' => 45000, 'youthline_share' => 45000,
    ]);
    expect(Fee::query()->where('term_id', $this->t2->id)->sole()->approval_status)->toBe(FeeApproval::Approved);
});

it('validates fee amounts and one entry per term', function () {
    actingAs($this->admin);
    $this->post(route('students.fees.store', $this->student), ['term_id' => $this->t1->id, 'amount_owed' => 100, 'youthline_share' => 200])
        ->assertSessionHasErrors('youthline_share');
    $this->post(route('students.fees.store', $this->student), ['term_id' => $this->t1->id, 'amount_owed' => 100, 'youthline_share' => 100])
        ->assertSessionHasNoErrors();
    $this->post(route('students.fees.store', $this->student), ['term_id' => $this->t1->id, 'amount_owed' => 100, 'youthline_share' => 100])
        ->assertSessionHasErrors('term_id');
});

// --- Payments & approvals ----------------------------------------------------

it('counts small payments straight away and holds over-limit ones for the Admin', function () {
    $fee = makeFee($this->student, $this->t1, 900000);

    actingAs($this->manager);
    pay($fee, ['amount' => 100000, 'receipt' => UploadedFile::fake()->create('receipt.pdf', 50, 'application/pdf')])->assertSessionHasNoErrors();
    pay($fee, ['amount' => 600000])->assertSessionHasNoErrors(); // over the 500,000 default limit

    [$small, $big] = Payment::query()->oldest('id')->get();
    expect($small->status)->toBe(PaymentStatus::Paid)
        ->and($small->receipt->type->value)->toBe('receipt')
        ->and($big->status)->toBe(PaymentStatus::PendingApproval)
        ->and($fee->fresh()->balance())->toBe(800000.0); // pending doesn't count

    Notification::assertSentTo($this->admin, PaymentAwaitingApproval::class);

    actingAs($this->manager)->put(route('finance.approvals.payments.approve', $big))->assertForbidden();
    actingAs($this->admin)->put(route('finance.approvals.payments.approve', $big))->assertSessionHas('success');

    expect($big->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($big->fresh()->approved_by)->toBe($this->admin->id)
        ->and($fee->fresh()->balance())->toBe(200000.0);
});

it('lets the Admin reject with a reason, and an Admin\'s own large payment is approved on entry', function () {
    $fee = makeFee($this->student, $this->t1, 2000000);

    actingAs($this->manager);
    pay($fee, ['amount' => 700000]);
    $pending = Payment::query()->sole();

    actingAs($this->admin)->put(route('finance.approvals.payments.reject', $pending), ['reason' => ''])->assertSessionHasErrors('reason');
    actingAs($this->admin)->put(route('finance.approvals.payments.reject', $pending), ['reason' => 'Wrong school account'])->assertSessionHasNoErrors();
    expect($pending->fresh()->status)->toBe(PaymentStatus::Rejected)
        ->and($pending->fresh()->rejection_reason)->toBe('Wrong school account');

    actingAs($this->admin);
    pay($fee, ['amount' => 700000]);
    $own = Payment::query()->latest('id')->first();
    expect($own->status)->toBe(PaymentStatus::Paid)->and($own->approved_by)->toBe($this->admin->id);
});

it('never lets a payment exceed what is still owed', function () {
    $fee = makeFee($this->student, $this->t1, 45000);
    actingAs($this->manager);

    pay($fee, ['amount' => 50000])->assertSessionHasErrors('amount');
    pay($fee, ['amount' => 45000])->assertSessionHasNoErrors();
    pay($fee, ['amount' => 1])->assertSessionHasErrors('amount');
    pay($fee, ['amount' => 10, 'paid_on' => now()->addDay()->toDateString()])->assertSessionHasErrors('paid_on');
});

it('has no way to delete a payment — managers mark it as an error instead', function () {
    $fee = makeFee($this->student, $this->t1, 45000);
    actingAs($this->manager);
    pay($fee, ['amount' => 45000]);
    $payment = Payment::query()->sole();

    expect(fn () => route('students.payments.destroy', [$this->student, $fee, $payment]))->toThrow(Exception::class);
    $attempt = actingAs($this->admin)->delete("/students/{$this->student->id}/fees/{$fee->id}/payments/{$payment->id}");
    expect($attempt->status())->toBeIn([404, 405]);

    actingAs($this->manager)->put(route('students.payments.error', [$this->student, $fee, $payment]), ['reason' => 'Duplicate entry'])
        ->assertSessionHasNoErrors();

    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::Error)
        ->and($payment->error_reason)->toBe('Duplicate entry')
        ->and($payment->error_marked_by)->toBe($this->manager->id)
        ->and($fee->fresh()->balance())->toBe(45000.0) // no longer counts
        ->and(Payment::query()->count())->toBe(1); // still in the ledger

    // A fee with payments can't be deleted either.
    actingAs($this->manager)->delete(route('students.fees.destroy', [$this->student, $fee]))->assertSessionHasErrors('fee');
    expect(Fee::query()->count())->toBe(1);
});

it('keeps a payment\'s receipt from being deleted on its own', function () {
    $fee = makeFee($this->student, $this->t1, 45000);
    actingAs($this->manager);
    pay($fee, ['amount' => 1000, 'receipt' => UploadedFile::fake()->create('r.pdf', 10, 'application/pdf')]);
    $receipt = Document::query()->where('type', 'receipt')->sole();

    actingAs($this->admin)->delete(route('students.documents.destroy', [$this->student, $receipt]))->assertSessionHas('error');
    expect(Document::query()->count())->toBe(1);
});

// --- Balances ----------------------------------------------------------------

it('reconciles balances per student and per school', function () {
    $other = makeLearner(attributes: ['first_name' => 'Kondwani']);
    $university = Institution::create(['name' => 'MUST', 'type' => 'university']);

    $a1 = makeFee($this->student, $this->t1, 45000, ['institution_id' => $this->school->id]);
    $a2 = makeFee($this->student, $this->t2, 45000, ['institution_id' => $this->school->id]);
    $b1 = makeFee($other, $this->t1, 300000, ['institution_id' => $university->id]);
    makeFee($other, $this->t2, 300000, ['institution_id' => $university->id, 'approval_status' => FeeApproval::Pending]); // not owed yet

    Payment::create(['fee_id' => $a1->id, 'amount' => 45000, 'status' => PaymentStatus::Paid]);
    Payment::create(['fee_id' => $a2->id, 'amount' => 20000, 'status' => PaymentStatus::Paid]);
    Payment::create(['fee_id' => $a2->id, 'amount' => 5000, 'status' => PaymentStatus::Error]);
    Payment::create(['fee_id' => $b1->id, 'amount' => 100000, 'status' => PaymentStatus::Paid]);
    Payment::create(['fee_id' => $b1->id, 'amount' => 50000, 'status' => PaymentStatus::PendingApproval]);

    $balances = app(BalanceService::class);

    expect($balances->totals())->toBe(['outstanding' => 225000.0, 'students' => 2, 'schools' => 2]);

    $byStudent = $balances->unpaidByStudent()->keyBy('student_id');
    expect($byStudent[$this->student->id]['balance'])->toBe(25000.0)
        ->and($byStudent[$other->id]['balance'])->toBe(200000.0)
        ->and($byStudent[$other->id]['awaiting_approval'])->toBe(50000.0);

    $bySchool = $balances->unpaidBySchool()->keyBy('school');
    expect($bySchool['MUST']['balance'])->toBe(200000.0)
        ->and($bySchool['Zomba Secondary']['balance'])->toBe(25000.0)
        ->and($balances->totals($this->t1->id)['outstanding'])->toBe(200000.0);

    // The sums agree from every angle.
    expect($byStudent->sum('balance'))->toBe($bySchool->sum('balance'));
});

it('shows the unpaid list and exports it as CSV', function () {
    makeFee($this->student, $this->t1, 45000, ['institution_id' => $this->school->id]);

    actingAs($this->manager)->get(route('finance.unpaid.index'))->assertOk();
    $csv = actingAs($this->manager)->get(route('finance.unpaid.export'))->assertOk()->streamedContent();

    expect($csv)->toContain('Thandiwe Banda')->toContain('45000.00');
    actingAs($this->outsider)->get(route('finance.unpaid.index'))->assertForbidden();
});

it('shows the approvals page to the Admin only', function () {
    actingAs($this->admin)->get(route('finance.approvals.index'))->assertOk();
    actingAs($this->manager)->get(route('finance.approvals.index'))->assertForbidden();
});
