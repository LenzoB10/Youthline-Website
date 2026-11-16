<?php

namespace App\Http\Controllers\Finance;

use App\Enums\FeeApproval;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReasonRequest;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\RepairProject;
use App\Services\Fees\FeeService;
use App\Services\Fees\PaymentService;
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
        protected PaymentService $payments,
        protected SystemSettingsService $settings,
    ) {}

    public function index(Request $request): Response
    {
        $term = fn ($t) => trim(($t?->academicYear?->name ?? '').' '.($t?->name ?? ''));

        return Inertia::render('Finance/Approvals', [
            'currency' => $this->settings->get()->currency,
            'paymentLimit' => (float) $this->settings->get()->payment_approval_limit,
            'payments' => Payment::query()
                ->pendingApproval()
                ->with(['fee.student', 'fee.term.academicYear', 'fee.institution', 'recorder:id,name', 'receipt'])
                ->oldest()
                ->get()
                ->map(fn (Payment $p) => [
                    'id' => $p->id,
                    'student_id' => $p->fee->student_id,
                    'student' => $p->fee->student->full_name,
                    'term' => $term($p->fee->term),
                    'school' => $p->fee->institution?->name,
                    'amount' => (float) $p->amount,
                    'paid_on' => $p->paid_on?->toDateString(),
                    'method' => $p->method,
                    'reference' => $p->reference,
                    'recorded_by' => $p->recorder?->name,
                    'recorded_at' => $p->created_at?->toIso8601String(),
                    'receipt_url' => $p->receipt ? route('students.documents.show', [$p->fee->student_id, $p->receipt_document_id]) : null,
                    'fee_share' => (float) $p->fee->youthline_share,
                    'fee_balance' => $p->fee->balance(),
                ]),
            'repairs' => $request->user()->can('approve repairs') ? RepairProject::query()
                ->where('approval_status', FeeApproval::Pending->value)
                ->with(['household.students:id,household_id,first_name,last_name', 'creator:id,name', 'photos'])
                ->oldest()
                ->get()
                ->map(fn (RepairProject $r) => [
                    'id' => $r->id,
                    'household' => $r->household?->head_name ?: 'Unnamed household',
                    'student_id' => $r->household?->students->first()?->id,
                    'students' => $r->household?->students->map->full_name->values()->all() ?? [],
                    'description' => $r->description,
                    'estimated_cost' => (float) $r->estimated_cost,
                    'photos' => $r->photos->count(),
                    'created_by' => $r->creator?->name,
                    'created_at' => $r->created_at?->toIso8601String(),
                ]) : [],
            'repairLimit' => (float) $this->settings->get()->repair_approval_limit,
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

    public function approvePayment(Request $request, Payment $payment): RedirectResponse
    {
        $this->payments->approve($payment, $request->user());

        return back()->with('success', 'Payment approved. It now counts towards the balance.');
    }

    public function rejectPayment(ReasonRequest $request, Payment $payment): RedirectResponse
    {
        $this->payments->reject($payment, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Payment rejected. The manager who recorded it has been told why.');
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
