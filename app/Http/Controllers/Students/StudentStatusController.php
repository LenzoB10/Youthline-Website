<?php

namespace App\Http\Controllers\Students;

use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\ChangeStudentStatusRequest;
use App\Models\Student;
use App\Services\Students\StudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('permission:change student status')]
class StudentStatusController extends Controller
{
    public function update(ChangeStudentStatusRequest $request, Student $student, StudentService $students): RedirectResponse
    {
        $to = StudentStatus::from($request->validated('status'));

        $students->changeStatus($student, $to, $request->validated('exit_reason'), $request->date('exit_date'));

        return back()->with('success', "{$student->full_name} is now {$to->label()}.");
    }
}
