<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\AlumniOutcomeRequest;
use App\Http\Requests\Support\ApplicationRequest;
use App\Http\Requests\Support\CareerInterestsRequest;
use App\Models\AlumniOutcome;
use App\Models\Application;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

#[Middleware('permission:manage careers', except: ['destroyApplication', 'destroyOutcome'])]
#[Middleware('permission:delete support records', only: ['destroyApplication', 'destroyOutcome'])]
class CareerController extends Controller
{
    public function updateInterests(CareerInterestsRequest $request, Student $student): RedirectResponse
    {
        DB::transaction(function () use ($request, $student) {
            $student->careerInterests()->delete();
            $student->careerInterests()->createMany(
                collect($request->validated('interests'))->map(fn ($interest) => ['interest' => trim($interest)])->all(),
            );
        });

        return back()->with('success', 'Career interests saved.');
    }

    public function storeApplication(ApplicationRequest $request, Student $student): RedirectResponse
    {
        $student->applications()->create($request->validated());

        return back()->with('success', 'Application recorded.');
    }

    public function updateApplication(ApplicationRequest $request, Student $student, Application $application): RedirectResponse
    {
        $application->update($request->validated());

        return back()->with('success', 'Application updated.');
    }

    public function destroyApplication(Student $student, Application $application): RedirectResponse
    {
        $application->delete();

        return back()->with('success', 'Application deleted.');
    }

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
