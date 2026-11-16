<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\AdvanceRepairRequest;
use App\Http\Requests\Support\HomeVisitRequest;
use App\Http\Requests\Support\RepairProjectRequest;
use App\Models\HomeVisit;
use App\Models\RepairProject;
use App\Models\Student;
use App\Services\Support\HomeVisitService;
use App\Services\Support\RepairService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;

/**
 * Visits and repairs belong to the student's household; every route checks that
 * the visit/repair really is that household's.
 */
#[Middleware('permission:manage home visits', only: ['storeVisit', 'storeRepair', 'advanceRepair'])]
#[Middleware('permission:delete support records', only: ['destroyVisit', 'destroyRepair'])]
class HomeVisitController extends Controller
{
    public function __construct(
        protected HomeVisitService $visits,
        protected RepairService $repairs,
    ) {}

    public function storeVisit(HomeVisitRequest $request, Student $student): RedirectResponse
    {
        $household = $student->household ?? abort(422, 'Link this student to a household first.');

        $this->visits->record($household, $request->validated(), $request->file('photos', []), $request->user());

        return back()->with('success', 'Home visit recorded.');
    }

    public function destroyVisit(Student $student, HomeVisit $homeVisit): RedirectResponse
    {
        abort_unless($student->household_id && $homeVisit->household_id === $student->household_id, 404);
        $this->visits->delete($homeVisit);

        return back()->with('success', 'Visit deleted.');
    }

    public function storeRepair(RepairProjectRequest $request, Student $student): RedirectResponse
    {
        $household = $student->household ?? abort(422, 'Link this student to a household first.');

        $repair = $this->repairs->create($household, $request->validated(), $request->file('photos', []), $request->user());

        return back()->with('success', $repair->isApproved()
            ? 'Repair planned.'
            : 'Repair planned. The budget is over the approval limit, so it waits for an Admin before work starts.');
    }

    public function advanceRepair(AdvanceRepairRequest $request, Student $student, RepairProject $repairProject): RedirectResponse
    {
        abort_unless($student->household_id && $repairProject->household_id === $student->household_id, 404);

        $repair = $this->repairs->advance($repairProject, $request->validated(), $request->file('photos', []), $request->user());

        return back()->with('success', 'Repair is now '.strtolower($repair->status->label()).'.');
    }

    public function destroyRepair(Student $student, RepairProject $repairProject): RedirectResponse
    {
        abort_unless($student->household_id && $repairProject->household_id === $student->household_id, 404);
        $this->repairs->delete($repairProject);

        return back()->with('success', 'Repair deleted.');
    }
}
