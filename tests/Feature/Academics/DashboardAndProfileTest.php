<?php

use App\Enums\PaymentStatus;
use App\Models\AcademicResult;
use App\Models\Payment;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

require_once __DIR__.'/helpers.php';

beforeEach(function () {
    ['admin' => $this->admin, 'manager' => $this->manager, 'outsider' => $this->outsider] = studentActors();
    $this->year = makeYear('2025', current: true);
    $this->t1 = makeTerm($this->year, 'Term 1', '2025-01-06');
    $this->t2 = makeTerm($this->year, 'Term 2', '2025-04-28', current: true);
    $this->student = makeLearner();
});

it('shows each person only the dashboard blocks they can act on', function () {
    $fee = makeFee($this->student, $this->t1, 900000);
    Payment::create(['fee_id' => $fee->id, 'amount' => 600000, 'status' => PaymentStatus::PendingApproval]);
    AcademicResult::create(['student_id' => $this->student->id, 'term_id' => $this->t1->id, 'exam_type' => 'term', 'average' => 75]);
    AcademicResult::create(['student_id' => $this->student->id, 'term_id' => $this->t2->id, 'exam_type' => 'term', 'average' => 55]);

    actingAs($this->admin)->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('students.active', 1)
            ->where('fees.outstanding', 900000)
            ->where('approvals.payments', 1)
            ->where('gradeDrops.0.student', 'Thandiwe Banda')
            ->where('gradeDrops.0.change', -20)
        );

    actingAs($this->manager)->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('approvals', null)->where('fees.students', 1));

    actingAs($this->outsider)->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('students', null)->where('fees', null)->where('approvals', null)->where('gradeDrops', null));
});

it('gives the student profile its results, fees and balances', function () {
    $fee = makeFee($this->student, $this->t1, 45000);
    Payment::create(['fee_id' => $fee->id, 'amount' => 20000, 'status' => PaymentStatus::Paid, 'method' => 'Cash']);

    actingAs($this->manager)->get(route('students.show', $this->student))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('fees', 1)
            ->where('fees.0.state', 'part_paid')
            ->where('fees.0.balance', 25000)
            ->where('feeTotals.balance', 25000)
            ->where('finance.currency', 'MWK')
            ->has('options.terms', 2)
            ->has('options.paymentMethods')
        );
});
