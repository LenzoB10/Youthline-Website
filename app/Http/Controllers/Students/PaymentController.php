<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReasonRequest;
use App\Http\Requests\Students\PaymentRequest;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;
use App\Services\Fees\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;

/**
 * There is deliberately no delete: the ledger keeps every payment.
 */
#[Middleware('permission:manage fees')]
class PaymentController extends Controller
{
    public function __construct(protected PaymentService $payments) {}

    public function store(PaymentRequest $request, Student $student, Fee $fee): RedirectResponse
    {
        $this->payments->record($fee, $request->validated(), $request->file('receipt'), $request->user());

        return back()->with('success', 'Payment recorded.');
    }

    public function markError(ReasonRequest $request, Student $student, Fee $fee, Payment $payment): RedirectResponse
    {
        $this->payments->markError($payment, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Payment marked as an error. It stays in the record but no longer counts.');
    }
}
