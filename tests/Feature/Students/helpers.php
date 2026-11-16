<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

/**
 * Shared set-up for the student tests: the REAL roles seeder, so these tests also
 * prove the Manager/Admin permissions match the System Guide.
 *
 * @return array{admin: User, manager: User, outsider: User}
 */
function studentActors(): array
{
    test()->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    return ['admin' => $admin, 'manager' => $manager, 'outsider' => User::factory()->create()];
}

function makeYear(string $name, bool $current = false): AcademicYear
{
    return AcademicYear::create([
        'name' => $name,
        'start_date' => "{$name}-01-01",
        'end_date' => "{$name}-12-15",
        'is_current' => $current,
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function makeLearner(string $status = 'active', array $attributes = []): Student
{
    return Student::create([
        'first_name' => 'Thandiwe',
        'last_name' => 'Banda',
        'status' => $status,
        ...$attributes,
    ]);
}

function enrol(Student $student, AcademicYear $year, string $level, array $attributes = []): Enrollment
{
    return Enrollment::create([
        'student_id' => $student->id,
        'academic_year_id' => $year->id,
        'level' => $level,
        ...$attributes,
    ]);
}

/**
 * A valid "Add student" payload.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function studentPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Chisomo',
        'last_name' => 'Nkhoma',
        'date_of_birth' => '2009-04-12',
        'gender' => 'female',
        'home_location' => 'Ndirande, Blantyre',
        'guardian_name' => 'Mary Nkhoma',
        'guardian_phone' => '+265 888 111 222',
        'guardian_relationship' => 'Mother',
        'household_mode' => 'none',
        'program_ids' => [],
        ...$overrides,
    ];
}
