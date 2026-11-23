<?php

namespace App\Http\Controllers\Finance;

use App\Enums\FeeApproval;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReasonRequest;
use App\Models\Fee;
use App\Services\Fees\FeeService;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:approve payments')]
class ApprovalController extends Controller
{
    public function __construct(
        protected FeeService $fees,
        protected SystemSettingsService $settings,
    ) {}

    public function index(Request $request): Response
    {
        $term = fn ($t) => trim(($t?->academicYear?->name ?? '').' '.($t?->name ?? ''));

        return Inertia::render('Finance/Approvals', [
            'currency' => $this->settings->get()->currency,
            'scholarships' => Fee::query()
                ->where('approval_status', FeeApproval::Pending->value)
                ->with(['student', 'term.academicYear', 'institution', 'creator:id,name'])
                ->oldest()
                ->get()
                ->map(fn (Fee $f) => [
                    'id' => $f->id,
                    'student_id' => $f->student_id,
                    'student' => $f->student->full_name,
                    'term' => $term($f->term),
                    'school' => $f->institution?->name,
                    'amount_owed' => (float) $f->amount_owed,
                    'youthline_share' => (float) $f->youthline_share,
                    'notes' => $f->notes,
                    'created_by' => $f->creator?->name,
                    'created_at' => $f->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function approveScholarship(Request $request, Fee $fee): RedirectResponse
    {
        $this->fees->approve($fee, $request->user());

        return back()->with('success', 'Scholarship approved. Payments can now be recorded for it.');
    }

    public function rejectScholarship(ReasonRequest $request, Fee $fee): RedirectResponse
    {
        $this->fees->reject($fee, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Scholarship rejected. The manager who added it has been told why.');
    }
}
