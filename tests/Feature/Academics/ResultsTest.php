<?php

use App\Models\AcademicResult;
use App\Models\Document;
use App\Notifications\GradeDropAlert;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

require_once __DIR__.'/helpers.php';

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
    ['admin' => $this->admin, 'manager' => $this->manager, 'outsider' => $this->outsider] = studentActors();

    $this->year = makeYear('2025', current: true);
    $this->t1 = makeTerm($this->year, 'Term 1', '2025-01-06');
    $this->t2 = makeTerm($this->year, 'Term 2', '2025-04-28');
    $this->t3 = makeTerm($this->year, 'Term 3', '2025-09-01', current: true);
    $this->student = makeLearner();
    enrol($this->student, $this->year, 'form_2');
});

function recordResult(array $data)
{
    return test()->post(route('students.results.store', test()->student), [
        'exam_type' => 'term',
        'grades' => [['subject' => 'English', 'grade' => 'B'], ['subject' => '', 'grade' => '']],
        ...$data,
    ]);
}

it('records a term result with subject grades and a report card', function () {
    actingAs($this->manager);
    recordResult([
        'term_id' => $this->t1->id,
        'average' => 72.5,
        'position' => '4 of 40',
        'report_card' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
    ])->assertSessionHas('success');

    $result = AcademicResult::query()->sole();
    expect((float) $result->average)->toBe(72.5)
        ->and($result->enrollment_id)->not->toBeNull()
        ->and($result->subjectGrades()->pluck('subject')->all())->toBe(['English']) // blank row skipped
        ->and($result->reportCard->type->value)->toBe('report_card');

    Storage::disk('local')->assertExists($result->reportCard->file_path);
});

it('records MSCE as aggregate points instead of an average', function () {
    actingAs($this->manager);

    recordResult(['term_id' => $this->t3->id, 'exam_type' => 'msce'])->assertSessionHasErrors('msce_points');
    recordResult(['term_id' => $this->t3->id, 'exam_type' => 'msce', 'msce_points' => 5])->assertSessionHasErrors('msce_points');
    recordResult(['term_id' => $this->t3->id, 'exam_type' => 'msce', 'msce_points' => 18, 'average' => 90])->assertSessionHasNoErrors();

    $result = AcademicResult::query()->sole();
    expect($result->msce_points)->toBe(18)->and($result->average)->toBeNull();
});

it('allows only one result of a type per term', function () {
    actingAs($this->manager);
    recordResult(['term_id' => $this->t1->id, 'average' => 60])->assertSessionHasNoErrors();
    recordResult(['term_id' => $this->t1->id, 'average' => 65])->assertSessionHasErrors('term_id');
});

it('raises an alert when a term average drops sharply, and not for small dips', function () {
    actingAs($this->manager);
    recordResult(['term_id' => $this->t1->id, 'average' => 72]);
    recordResult(['term_id' => $this->t2->id, 'average' => 68]); // -4: a dip, not an alert

    Notification::assertNothingSent();

    recordResult(['term_id' => $this->t3->id, 'average' => 54]); // -14 on term 2

    Notification::assertSentTo([$this->admin, $this->manager], GradeDropAlert::class);
    Notification::assertNotSentTo($this->outsider, GradeDropAlert::class);

    // The profile flags it too.
    actingAs($this->manager)->get(route('students.show', $this->student))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('results.0.term', '2025 Term 3')
            ->where('results.0.change', -14)
            ->where('results.0.is_drop', true)
            ->where('results.1.is_drop', false)
        );
});

it('uses the Admin\'s threshold from Settings', function () {
    actingAs($this->admin)->put(route('admin.settings.approvals.update'), [
        'currency' => 'MWK', 'require_payment_approval' => true, 'payment_approval_limit' => 500000,
        'require_scholarship_approval' => true, 'require_repair_approval' => true, 'repair_approval_limit' => 1000000,
        'counselling_notes_visibility' => 'author', 'grade_drop_threshold' => 3,
    ])->assertSessionHasNoErrors();

    actingAs($this->manager);
    recordResult(['term_id' => $this->t1->id, 'average' => 72]);
    recordResult(['term_id' => $this->t2->id, 'average' => 68]);

    Notification::assertSentTo($this->manager, GradeDropAlert::class);
});

it('escapes student names in the alert message', function () {
    $this->student->update(['first_name' => '<script>alert(1)</script>']);
    actingAs($this->manager);
    recordResult(['term_id' => $this->t1->id, 'average' => 80]);
    recordResult(['term_id' => $this->t2->id, 'average' => 50]);

    Notification::assertSentTo($this->manager, GradeDropAlert::class, function (GradeDropAlert $alert) {
        $message = $alert->toArray($this->manager)['message'];

        return ! str_contains($message, '<script>') && str_contains($message, '&lt;script&gt;');
    });
});

it('lets managers edit results but only the Admin delete them, keeping the report card', function () {
    actingAs($this->manager);
    recordResult(['term_id' => $this->t1->id, 'average' => 60, 'report_card' => UploadedFile::fake()->create('r.pdf', 10, 'application/pdf')]);
    $result = AcademicResult::query()->sole();

    actingAs($this->manager)->post(route('students.results.update', [$this->student, $result]), [
        '_method' => 'put', 'term_id' => $this->t1->id, 'exam_type' => 'term', 'average' => 61,
        'grades' => [['subject' => 'Maths', 'grade' => 'A']],
    ])->assertSessionHasNoErrors();
    expect((float) $result->fresh()->average)->toBe(61.0)
        ->and($result->subjectGrades()->pluck('subject')->all())->toBe(['Maths']);

    actingAs($this->manager)->delete(route('students.results.destroy', [$this->student, $result]))->assertForbidden();
    actingAs($this->admin)->delete(route('students.results.destroy', [$this->student, $result]))->assertSessionHas('success');

    expect(AcademicResult::query()->count())->toBe(0)
        ->and(Document::query()->where('type', 'report_card')->count())->toBe(1);
});

it('keeps results away from people without permission', function () {
    actingAs($this->outsider);
    recordResult(['term_id' => $this->t1->id, 'average' => 60])->assertForbidden();
});
