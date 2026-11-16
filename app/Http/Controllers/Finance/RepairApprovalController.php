<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReasonRequest;
use App\Models\RepairProject;
use App\Services\Support\RepairService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('permission:approve repairs')]
class RepairApprovalController extends Controller
{
    public function __construct(protected RepairService $repairs) {}

    public function approve(Request $request, RepairProject $repairProject): RedirectResponse
    {
        $this->repairs->approve($repairProject, $request->user());

        return back()->with('success', 'Repair budget approved. Work can start.');
    }

    public function reject(ReasonRequest $request, RepairProject $repairProject): RedirectResponse
    {
        $this->repairs->reject($repairProject, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Repair budget rejected. The manager has been told why.');
    }
}
