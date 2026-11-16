<?php

use App\Enums\StudentStatus;
use App\Models\Enrollment;
use App\Models\Household;
use App\Models\Institution;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentProgram;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

require_once __DIR__.'/helpers.php';

beforeEach(function () {
    ['admin' => $this->admin, 'manager' => $this->manager, 'outsider' => $this->outsider] = studentActors();
    $this->year = makeYear('2026', current: true);
    $this->school = Institution::create(['name' => 'Blantyre Secondary', 'type' => 'secondary']);
    $this->education = Program::create(['name' => 'Educational Support', 'slug' => 'education']);
    $this->mentorship = Program::create(['name' => 'Mentorship', 'slug' => 'mentorship']);
});

// --- Access ------------------------------------------------------------------

it('requires login and the student permissions', function () {
    get(route('students.index'))->assertRedirect(route('login'));
    actingAs($this->outsider)->get(route('students.index'))->assertForbidden();
    actingAs($this->outsider)->get(route('students.create'))->assertForbidden();
});

it('lets managers view, add and edit students, but not delete them', function () {
    $student = makeLearner();

    actingAs($this->manager)->get(route('students.index'))->assertOk();
    actingAs($this->manager)->get(route('students.create'))->assertOk();
    actingAs($this->manager)->get(route('students.show', $student))->assertOk();
    actingAs($this->manager)->get(route('students.edit', $student))->assertOk();
    actingAs($this->manager)
        ->delete(route('students.destroy', $student), ['confirm_name' => 'Thandiwe Banda'])
        ->assertForbidden();

    expect(Student::query()->count())->toBe(1);
});

// --- Registration ------------------------------------------------------------

it('registers a student as an applicant with this year\'s level, programs and a new household', function () {
    $response = actingAs($this->manager)->post(route('students.store'), studentPayload([
        'household_mode' => 'new',
        'household' => ['head_name' => 'Mary Nkhoma', 'district' => 'Blantyre', 'members_count' => 5],
        'institution_name' => '  '.strtoupper($this->school->name).' ', // typed loosely: still matches
        'level' => 'form_2',
        'program_ids' => [$this->education->id, $this->mentorship->id],
    ]));

    $student = Student::query()->firstOrFail();
    $response->assertRedirect(route('students.show', $student));

    expect($student->status)->toBe(StudentStatus::Applicant)
        ->and($student->created_by)->toBe($this->manager->id)
        ->and($student->household->head_name)->toBe('Mary Nkhoma')
        ->and($student->activePrograms()->pluck('programs.id')->sort()->values()->all())
        ->toBe([$this->education->id, $this->mentorship->id]);

    $enrollment = $student->enrollments()->sole();
    expect($enrollment->academic_year_id)->toBe($this->year->id)
        ->and($enrollment->institution_id)->toBe($this->school->id)
        ->and($enrollment->level)->toBe('form_2');
});

it('links brothers and sisters to the same household', function () {
    $household = Household::create(['head_name' => 'Gift Moyo']);
    $sibling = makeLearner(attributes: ['household_id' => $household->id]);

    actingAs($this->manager)->post(route('students.store'), studentPayload([
        'household_mode' => 'existing',
        'household_id' => $household->id,
    ]))->assertSessionHasNoErrors();

    $new = Student::query()->whereKeyNot($sibling->id)->sole();
    expect($new->household_id)->toBe($household->id);

    actingAs($this->manager)->get(route('students.show', $new))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Students/Show')
            ->has('household.siblings', 1)
            ->where('household.siblings.0.id', $sibling->id));
});

it('validates the registration form', function () {
    actingAs($this->manager)
        ->post(route('students.store'), studentPayload([
            'first_name' => '',
            'gender' => 'unknown',
            'household_mode' => 'existing',
            'level' => 'form_9',
            'program_ids' => [999],
        ]))
        ->assertSessionHasErrors(['first_name', 'gender', 'household_id', 'level', 'program_ids.0']);

    expect(Student::query()->count())->toBe(0);
});

it('needs a current academic year before a level can be recorded', function () {
    $this->year->update(['is_current' => false]);

    actingAs($this->manager)
        ->post(route('students.store'), studentPayload(['level' => 'form_1']))
        ->assertSessionHasErrors('level');
});

// --- Editing -----------------------------------------------------------------

it('ends programs that are unticked instead of deleting them', function () {
    $student = makeLearner();
    StudentProgram::create(['student_id' => $student->id, 'program_id' => $this->education->id, 'start_date' => '2025-01-10']);

    actingAs($this->manager)
        ->put(route('students.update', $student), studentPayload([
            'first_name' => 'Thandiwe',
            'last_name' => 'Banda-Phiri',
            'program_ids' => [$this->mentorship->id],
        ]))
        ->assertRedirect(route('students.show', $student));

    $rows = StudentProgram::query()->where('student_id', $student->id)->get()->keyBy('program_id');

    expect($student->fresh()->last_name)->toBe('Banda-Phiri')
        ->and($rows)->toHaveCount(2)
        ->and($rows[$this->education->id]->end_date?->isToday())->toBeTrue()
        ->and($rows[$this->mentorship->id]->end_date)->toBeNull();
});

it('does not let the edit form rewrite school or level', function () {
    $student = makeLearner();

    actingAs($this->manager)
        ->put(route('students.update', $student), studentPayload(['level' => 'form_4']))
        ->assertSessionHasErrors('level');
});

// --- List --------------------------------------------------------------------

it('searches by full name, guardian and phone, and filters by status and level', function () {
    $thandi = makeLearner('active', ['guardian_name' => 'Agnes Banda']);
    enrol($thandi, $this->year, 'form_3');
    $limbani = makeLearner('applicant', ['first_name' => 'Limbani', 'last_name' => 'Moyo', 'guardian_phone' => '0999 555 123']);
    enrol($limbani, $this->year, 'form_1');

    $names = fn (array $query) => collect(
        actingAs($this->manager)->get(route('students.index', $query))->viewData('page')['props']['students']['data']
    )->pluck('full_name')->all();

    expect($names(['search' => 'Thandiwe Banda']))->toBe(['Thandiwe Banda'])
        ->and($names(['search' => 'Agnes']))->toBe(['Thandiwe Banda'])
        ->and($names(['search' => '555']))->toBe(['Limbani Moyo'])
        ->and($names(['status' => 'applicant']))->toBe(['Limbani Moyo'])
        ->and($names(['level' => 'form_3']))->toBe(['Thandiwe Banda'])
        ->and($names(['sort_by' => 'guardian_phone']))->toHaveCount(2); // unknown sort falls back safely
});

// --- Deletion ----------------------------------------------------------------

it('lets only the Admin delete a student permanently, after typing their name', function () {
    $student = makeLearner();
    enrol($student, $this->year, 'form_1');

    actingAs($this->admin)
        ->delete(route('students.destroy', $student), ['confirm_name' => 'Someone Else'])
        ->assertSessionHasErrors('confirm_name');
    expect(Student::query()->count())->toBe(1);

    actingAs($this->admin)
        ->delete(route('students.destroy', $student), ['confirm_name' => 'thandiwe banda'])
        ->assertRedirect(route('students.index'));

    expect(Student::query()->count())->toBe(0)
        ->and(Enrollment::query()->count())->toBe(0);
});

it('records every change to a student in the audit log', function () {
    actingAs($this->manager)->post(route('students.store'), studentPayload());

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => Student::class,
        'subject_id' => Student::query()->value('id'),
        'causer_id' => $this->manager->id,
    ]);
});

it('adds a new school when the typed name is not on the list yet', function () {
    actingAs($this->manager)->post(route('students.store'), studentPayload([
        'institution_name' => 'Chancellor College',
        'level' => 'year_1',
    ]))->assertSessionHasNoErrors();

    $school = Institution::query()->where('name', 'Chancellor College')->sole();
    expect($school->type->value)->toBe('university')
        ->and(Student::query()->firstOrFail()->currentEnrollment->institution_id)->toBe($school->id);
});
