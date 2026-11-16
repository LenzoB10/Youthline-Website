<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\AcademicResultRequest;
use App\Models\AcademicResult;
use App\Models\Student;
use App\Services\Academics\ResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('permission:record results', only: ['store', 'update'])]
#[Middleware('permission:delete results', only: ['destroy'])]
class AcademicResultController extends Controller
{
    public function __construct(protected ResultService $results) {}

    public function store(AcademicResultRequest $request, Student $student): RedirectResponse
    {
        $this->results->record($student, $request->validated(), $request->file('report_card'), $request->user());

        return back()->with('success', 'Result recorded.');
    }

    public function update(AcademicResultRequest $request, Student $student, AcademicResult $result): RedirectResponse
    {
        $this->results->update($result, $request->validated(), $request->file('report_card'), $request->user());

        return back()->with('success', 'Result updated.');
    }

    public function destroy(Student $student, AcademicResult $result): RedirectResponse
    {
        $this->results->delete($result);

        return back()->with('success', 'Result deleted. The report card is still in the student\'s documents.');
    }
}
