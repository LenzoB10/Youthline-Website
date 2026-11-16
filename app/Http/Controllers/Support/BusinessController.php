<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\BusinessCheckinRequest;
use App\Http\Requests\Support\BusinessRequest;
use App\Http\Requests\Support\LoanRepaymentRequest;
use App\Models\Business;
use App\Models\Student;
use App\Services\Support\BusinessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('permission:manage businesses', except: ['destroy'])]
#[Middleware('permission:delete support records', only: ['destroy'])]
class BusinessController extends Controller
{
    public function __construct(protected BusinessService $businesses) {}

    public function store(BusinessRequest $request, Student $student): RedirectResponse
    {
        $this->businesses->create($student, $request->validated());

        return back()->with('success', 'Business recorded.');
    }

    public function update(BusinessRequest $request, Student $student, Business $business): RedirectResponse
    {
        $this->businesses->update($business, $request->validated());

        return back()->with('success', 'Business updated.');
    }

    public function checkIn(BusinessCheckinRequest $request, Student $student, Business $business): RedirectResponse
    {
        $this->businesses->checkIn($business, $request->validated(), $request->user());

        return back()->with('success', 'Check-in recorded.');
    }

    public function repay(LoanRepaymentRequest $request, Student $student, Business $business): RedirectResponse
    {
        $this->businesses->recordRepayment($business, $request->validated(), $request->user());

        return back()->with('success', 'Repayment recorded.');
    }

    public function destroy(Student $student, Business $business): RedirectResponse
    {
        $business->delete();

        return back()->with('success', 'Business deleted.');
    }
}
