<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\AlumniOutcomeRequest;
use App\Models\AlumniOutcome;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('permission:manage careers', except: ['destroyOutcome'])]
#[Middleware('permission:delete support records', only: ['destroyOutcome'])]
class CareerController extends Controller
{
    public function storeOutcome(AlumniOutcomeRequest $request, Student $student): RedirectResponse
    {
        $student->alumniOutcomes()->create($request->validated());

        return back()->with('success', 'Outcome recorded.');
    }

    public function destroyOutcome(Student $student, AlumniOutcome $alumniOutcome): RedirectResponse
    {
        $alumniOutcome->delete();

        return back()->with('success', 'Outcome deleted.');
    }
}
