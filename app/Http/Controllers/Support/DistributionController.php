<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\DistributionRequest;
use App\Models\Distribution;
use App\Models\Student;
use App\Services\Support\BasicNeedsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('permission:manage basic needs', only: ['store'])]
#[Middleware('permission:delete support records', only: ['destroy'])]
class DistributionController extends Controller
{
    public function __construct(protected BasicNeedsService $basicNeeds) {}

    public function store(DistributionRequest $request, Student $student): RedirectResponse
    {
        $this->basicNeeds->record($student, $request->validated(), $request->user());

        return back()->with('success', 'Support recorded.');
    }

    public function destroy(Student $student, Distribution $distribution): RedirectResponse
    {
        $distribution->delete();

        return back()->with('success', 'Entry deleted.');
    }
}
