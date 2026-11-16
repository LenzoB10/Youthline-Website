<?php

namespace App\Http\Controllers\Students;

use App\Enums\EducationLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\PlaceEnrollmentRequest;
use App\Models\Student;
use App\Services\Students\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;

/**
 * "Change school / level" for the current year, e.g. Form 4 → university Year 1.
 */
#[Middleware('permission:change student status')]
class EnrollmentController extends Controller
{
    public function update(PlaceEnrollmentRequest $request, Student $student, EnrollmentService $enrollments): RedirectResponse
    {
        $enrollment = $enrollments->placeInCurrentYear($student, $request->validated());
        $level = EducationLevel::from($enrollment->level)->label();

        return back()->with('success', "{$student->full_name} is recorded at {$level} for {$enrollment->academicYear->name}.");
    }
}
