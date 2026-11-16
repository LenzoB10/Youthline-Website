<?php

namespace App\Services\Students;

use App\Models\Enrollment;
use App\Models\Student;
use App\Services\Settings\AcademicCalendarService;
use RuntimeException;

/**
 * Sets a student's school and level for the CURRENT academic year — e.g. Form 4 to
 * university: new institution, Year 1, same student, earlier years untouched.
 */
class EnrollmentService
{
    public function __construct(protected AcademicCalendarService $calendar) {}

    /**
     * @param  array{institution_id: ?int, level: string, course: ?string, is_repeating?: bool}  $data
     */
    public function placeInCurrentYear(Student $student, array $data): Enrollment
    {
        $year = $this->calendar->current()
            ?? throw new RuntimeException('Set the current academic year first.');

        return Enrollment::query()->updateOrCreate(
            ['student_id' => $student->id, 'academic_year_id' => $year->id],
            [
                'institution_id' => $data['institution_id'] ?? null,
                'level' => $data['level'],
                'course' => $data['course'] ?? null,
                'is_repeating' => (bool) ($data['is_repeating'] ?? false),
            ],
        );
    }
}
