<?php

use App\Contracts\Students\LevelProgression;
use App\Enums\EducationLevel;
use App\Enums\StudentStatus;
use App\Models\Enrollment;
use App\Models\ExitReason;
use App\Models\Institution;
use App\Models\Program;
use App\Models\StudentProgram;
use App\Services\Students\Promotion\PromotionPlanner;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

require_once __DIR__.'/helpers.php';

beforeEach(function () {
    ['admin' => $this->admin, 'manager' => $this->manager, 'outsider' => $this->outsider] = studentActors();
    ExitReason::create(['name' => 'Family relocated']);
    ExitReason::create(['name' => 'Completed studies']);
    ExitReason::create(['name' => 'Retired reason', 'is_active' => false]);
});

// --- Status changes ----------------------------------------------------------

it('activates an applicant after assessment', function () {
    $student = makeLearner('applicant');

    actingAs($this->manager)
        ->put(route('students.status.update', $student), ['status' => 'active'])
        ->assertSessionHas('success');

    expect($student->fresh()->status)->toBe(StudentStatus::Active);
});

it('requires an active exit reason whenever a student leaves', function () {
    $student = makeLearner();

    actingAs($this->manager)
        ->put(route('students.status.update', $student), ['status' => 'dropped_out'])
        ->assertSessionHasErrors('exit_reason');

    actingAs($this->manager)
        ->put(route('students.status.update', $student), ['status' => 'dropped_out', 'exit_reason' => 'Retired reason'])
        ->assertSessionHasErrors('exit_reason');

    actingAs($this->manager)
        ->put(route('students.status.update', $student), [
            'status' => 'dropped_out', 'exit_reason' => 'Family relocated', 'exit_date' => '2026-03-01',
        ])
        ->assertSessionHasNoErrors();

    $student->refresh();
    expect($student->status)->toBe(StudentStatus::DroppedOut)
        ->and($student->exit_reason)->toBe('Family relocated')
        ->and($student->exit_date->toDateString())->toBe('2026-03-01');
});

it('rejects status changes that skip the lifecycle', function () {
    $applicant = makeLearner('applicant');

    actingAs($this->manager)
        ->put(route('students.status.update', $applicant), ['status' => 'graduated', 'exit_reason' => 'Completed studies'])
        ->assertSessionHasErrors('status');

    expect($applicant->fresh()->status)->toBe(StudentStatus::Applicant);
});

it('ends programs on a permanent exit but keeps them during a pause', function () {
    $program = Program::create(['name' => 'Mentorship', 'slug' => 'mentorship']);
    $paused = makeLearner();
    $graduate = makeLearner(attributes: ['first_name' => 'Tadala']);
    foreach ([$paused, $graduate] as $student) {
        StudentProgram::create(['student_id' => $student->id, 'program_id' => $program->id, 'start_date' => '2025-01-01']);
    }

    actingAs($this->manager)->put(route('students.status.update', $paused), ['status' => 'paused', 'exit_reason' => 'Family relocated']);
    actingAs($this->manager)->put(route('students.status.update', $graduate), ['status' => 'graduated', 'exit_reason' => 'Completed studies']);

    expect($paused->activePrograms()->count())->toBe(1)
        ->and($graduate->activePrograms()->count())->toBe(0)
        ->and(StudentProgram::query()->count())->toBe(2); // history kept
});

it('re-admits a student who left and clears the exit details', function () {
    $student = makeLearner('dropped_out', ['exit_reason' => 'Family relocated', 'exit_date' => '2025-06-01']);

    actingAs($this->manager)->put(route('students.status.update', $student), ['status' => 'active'])->assertSessionHasNoErrors();

    $student->refresh();
    expect($student->status)->toBe(StudentStatus::Active)
        ->and($student->exit_reason)->toBeNull()
        ->and($student->exit_date)->toBeNull();

    // The earlier exit stays in the audit log.
    $this->assertDatabaseHas('activity_log', ['subject_type' => $student::class, 'subject_id' => $student->id]);
});

it('keeps status changes away from users without permission', function () {
    $student = makeLearner('applicant');

    actingAs($this->outsider)->put(route('students.status.update', $student), ['status' => 'active'])->assertForbidden();
});

// --- Promotion ---------------------------------------------------------------

it('sorts active students into ready, decide and unplaced', function () {
    $last = makeYear('2025');
    $current = makeYear('2026', current: true);

    $formTwo = makeLearner(attributes: ['first_name' => 'Ada']);
    enrol($formTwo, $last, 'form_2');
    $formFour = makeLearner(attributes: ['first_name' => 'Bea']);
    enrol($formFour, $last, 'form_4');
    $yearFive = makeLearner(attributes: ['first_name' => 'Cal']);
    enrol($yearFive, $last, 'year_5');
    $noLevel = makeLearner(attributes: ['first_name' => 'Dan']);
    $placed = makeLearner(attributes: ['first_name' => 'Eve']);
    enrol($placed, $current, 'form_1');
    $applicant = makeLearner('applicant', ['first_name' => 'Fay']);
    enrol($applicant, $last, 'form_1');

    $plan = app(PromotionPlanner::class)->plan();

    expect($plan['year']->is($current))->toBeTrue()
        ->and(collect($plan['ready'])->pluck('student_id')->all())->toBe([$formTwo->id])
        ->and($plan['ready'][0]['to_level'])->toBe('form_3')
        ->and($plan['ready'][0]['can_promote'])->toBeTrue()
        ->and(collect($plan['decide'])->pluck('student_id')->sort()->values()->all())->toBe([$formFour->id, $yearFive->id])
        ->and(collect($plan['unplaced'])->pluck('student_id')->all())->toBe([$noLevel->id]);
});

it('promotes the students chosen, repeats others, and leaves the rest for later', function () {
    $last = makeYear('2025');
    makeYear('2026', current: true);
    $school = Institution::create(['name' => 'Zomba Secondary', 'type' => 'secondary']);

    $moving = makeLearner(attributes: ['first_name' => 'Ada']);
    $old = enrol($moving, $last, 'form_2', ['institution_id' => $school->id]);
    $repeater = makeLearner(attributes: ['first_name' => 'Ben']);
    enrol($repeater, $last, 'form_3', ['institution_id' => $school->id]);
    $later = makeLearner(attributes: ['first_name' => 'Cat']);
    enrol($later, $last, 'form_1');
    $formFour = makeLearner(attributes: ['first_name' => 'Dee']);
    enrol($formFour, $last, 'form_4');

    actingAs($this->manager)
        ->post(route('students.promote.store'), ['decisions' => [
            $moving->id => 'promote',
            $repeater->id => 'repeat',
            $formFour->id => 'repeat', // top of a stage may repeat here
        ]])
        ->assertSessionHas('success', 'Promoted 1 student and recorded 2 repeating.');

    $current = fn ($student) => $student->enrollments()->whereHas('academicYear', fn ($q) => $q->where('is_current', true))->first();

    expect($current($moving)->level)->toBe('form_3')
        ->and($current($moving)->institution_id)->toBe($school->id)
        ->and($old->fresh()->level)->toBe('form_2')
        ->and($current($repeater)->level)->toBe('form_3')
        ->and($current($repeater)->is_repeating)->toBeTrue()
        ->and($current($formFour)->level)->toBe('form_4')
        ->and($current($later))->toBeNull(); // no decision → still waiting

    // Cat is still on the list; the others are not.
    expect(app(PromotionPlanner::class)->candidates()->keys()->all())->toBe([$later->id]);

    // Sending the same decisions again is refused — those students are already placed.
    actingAs($this->manager)
        ->post(route('students.promote.store'), ['decisions' => [$moving->id => 'promote']])
        ->assertSessionHasErrors('decisions');
    expect(Enrollment::query()->count())->toBe(7);
});

it('will not promote a Form 4 student automatically', function () {
    $last = makeYear('2025');
    makeYear('2026', current: true);
    $formFour = makeLearner();
    enrol($formFour, $last, 'form_4');

    actingAs($this->manager)
        ->post(route('students.promote.store'), ['decisions' => [$formFour->id => 'promote']])
        ->assertSessionHasErrors('decisions');

    expect($formFour->enrollments()->count())->toBe(1);
});

it('needs at least one decision and a valid choice', function () {
    $last = makeYear('2025');
    makeYear('2026', current: true);
    $student = makeLearner();
    enrol($student, $last, 'form_1');

    actingAs($this->manager)->post(route('students.promote.store'), ['decisions' => []])->assertSessionHasErrors('decisions');
    actingAs($this->manager)->post(route('students.promote.store'), ['decisions' => [$student->id => 'graduate']])->assertSessionHasErrors('decisions.'.$student->id);
});

it('uses the level rule bound to the LevelProgression contract', function () {
    $last = makeYear('2025');
    makeYear('2026', current: true);
    $student = makeLearner();
    enrol($student, $last, 'form_1');

    // A different rule (e.g. skipping a level) can be swapped in without touching promotion code.
    app()->bind(LevelProgression::class, fn () => new class implements LevelProgression
    {
        public function next(EducationLevel $level): ?EducationLevel
        {
            return EducationLevel::Form3;
        }
    });

    expect(app(PromotionPlanner::class)->plan()['ready'][0]['to_level'])->toBe('form_3');
});

it('shows the promotion screen and refuses to promote without a current year', function () {
    $student = makeLearner();
    enrol($student, makeYear('2025'), 'form_1');

    actingAs($this->manager)->get(route('students.promote.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Students/Promote')->where('year', null));

    actingAs($this->manager)
        ->post(route('students.promote.store'), ['decisions' => [$student->id => 'promote']])
        ->assertSessionHasErrors('decisions');
});

it('keeps promotion away from people without permission', function () {
    actingAs($this->outsider)->get(route('students.promote.index'))->assertForbidden();
});

// --- Form 4 to university ----------------------------------------------------

it('moves a Form 4 leaver to university as the same student, keeping their history', function () {
    $last = makeYear('2025');
    $current = makeYear('2026', current: true);
    $secondary = Institution::create(['name' => 'Blantyre Secondary', 'type' => 'secondary']);
    $university = Institution::create(['name' => 'MUST', 'type' => 'university']);

    $student = makeLearner();
    $formFour = enrol($student, $last, 'form_4', ['institution_id' => $secondary->id]);

    actingAs($this->manager)
        ->put(route('students.enrollment.update', $student), [
            'institution_id' => $university->id, 'level' => 'year_1', 'course' => 'BSc Computer Science',
        ])
        ->assertSessionHasNoErrors();

    // Correcting it updates the same row rather than adding a second one for 2026.
    actingAs($this->manager)
        ->put(route('students.enrollment.update', $student), [
            'institution_id' => $university->id, 'level' => 'year_1', 'course' => 'BSc Data Science',
        ]);

    $rows = $student->enrollments()->get()->keyBy('academic_year_id');
    expect($rows)->toHaveCount(2)
        ->and($rows[$current->id]->institution_id)->toBe($university->id)
        ->and($rows[$current->id]->course)->toBe('BSc Data Science')
        ->and($formFour->fresh()->level)->toBe('form_4')
        ->and($formFour->fresh()->institution_id)->toBe($secondary->id);
});
