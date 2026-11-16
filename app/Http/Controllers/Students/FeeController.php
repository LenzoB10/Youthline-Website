<?php

namespace App\Http\Controllers\Students;

use App\Enums\FeeApproval;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\FeeRequest;
use App\Models\Fee;
use App\Models\Student;
use App\Services\Fees\FeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('permission:manage fees')]
class FeeController extends Controller
{
    public function __construct(protected FeeService $fees) {}

    public function store(FeeRequest $request, Student $student): RedirectResponse
    {
        $fee = $this->fees->create($student, $request->validated(), $request->user());

        return back()->with('success', $fee->approval_status === FeeApproval::Pending
            ? 'Fees saved. This is a new scholarship, so it now waits for Admin approval before any payment.'
            : 'Fees saved.');
    }

    public function update(FeeRequest $request, Student $student, Fee $fee): RedirectResponse
    {
        $this->fees->update($fee, $request->validated());

        return back()->with('success', 'Fees updated.');
    }

    public function destroy(Student $student, Fee $fee): RedirectResponse
    {
        $this->fees->delete($fee);

        return back()->with('success', 'Fee entry deleted.');
    }
}
