<?php

use App\Models\AcademicResult;
use App\Models\AcademicYear;
use App\Models\AlumniOutcome;
use App\Models\Business;
use App\Models\Enrollment;
use App\Models\FamilySupport;
use App\Models\HomeVisit;
use App\Models\Household;
use App\Models\Program;
use App\Models\Student;
use App\Services\Website\ImpactMetricsService;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

function metricValue(string $key): int
{
    $metrics = app(ImpactMetricsService::class)->all();

    return collect($metrics)->firstWhere('key', $key)['value'];
}

function makeStudent(string $status = 'active'): Student
{
    return Student::create([
        'first_name' => 'Test',
        'last_name' => 'Student',
        'status' => $status,
    ]);
}

it('counts supported young people and excludes applicants', function () {
    makeStudent('active');
    makeStudent('graduated');
    makeStudent('applicant');

    expect(metricValue('supported'))->toBe(2)
        ->and(metricValue('graduates'))->toBe(1);
});

it('counts active students by their current-year level', function () {
    $year = AcademicYear::create(['name' => '2025', 'is_current' => true]);

    $secondary = makeStudent('active');
    Enrollment::create(['student_id' => $secondary->id, 'academic_year_id' => $year->id, 'level' => 'form_3']);

    $university = makeStudent('active');
    Enrollment::create(['student_id' => $university->id, 'academic_year_id' => $year->id, 'level' => 'year_1']);

    expect(metricValue('active_secondary'))->toBe(1)
        ->and(metricValue('active_university'))->toBe(1);
});

it('counts families, family support, businesses, alumni and MSCE from live records', function () {
    $visited = Household::create(['head_name' => 'Visited']);
    HomeVisit::create(['household_id' => $visited->id, 'visit_date' => now()]);
    Household::create(['head_name' => 'Never visited']);

    // Two entries for one family count as one family supported.
    FamilySupport::create(['household_id' => $visited->id, 'given_on' => now(), 'type' => 'food', 'description' => 'Maize']);
    FamilySupport::create(['household_id' => $visited->id, 'given_on' => now(), 'type' => 'home_repair', 'description' => 'Roof']);

    $student = makeStudent('graduated');
    Business::create(['student_id' => $student->id, 'name' => 'Tailoring']);
    AlumniOutcome::create(['student_id' => $student->id, 'outcome' => 'employed']);
    AcademicResult::create(['student_id' => $student->id, 'exam_type' => 'msce']);

    expect(metricValue('families'))->toBe(1)
        ->and(metricValue('family_support'))->toBe(1)
        ->and(metricValue('businesses'))->toBe(1)
        ->and(metricValue('alumni_active'))->toBe(1)
        ->and(metricValue('msce'))->toBe(1);
});

it('counts students per program', function () {
    $program = Program::create(['name' => 'Education', 'slug' => 'education']);
    makeStudent()->programs()->attach($program->id);

    expect(app(ImpactMetricsService::class)->programStudentCounts()[$program->id])->toBe(1);
});

it('no longer has an Our Impact page', function () {
    expect(Route::has('impact'))->toBeFalse();
    get('/impact')->assertNotFound();
});

it('passes a four-figure headline strip to the home and about pages', function (string $route) {
    get(route($route))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('impact', 4));
})->with(['home', 'about']);

it('never exposes student names in the impact figures', function () {
    Student::create(['first_name' => 'Tadala', 'last_name' => 'Confidential', 'status' => 'active']);

    get(route('home'))
        ->assertOk()
        ->assertDontSee('Tadala')
        ->assertDontSee('Confidential');
});
