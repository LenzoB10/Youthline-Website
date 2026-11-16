<?php

namespace App\Services\Fees;

use App\Enums\DocumentType;
use App\Enums\PaymentStatus;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\ApprovalDecided;
use App\Notifications\PaymentAwaitingApproval;
use App\Services\Notifications\AlertRecipients;
use App\Services\Settings\SystemSettingsService;
use App\Services\Students\StudentDocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * The payment ledger. Payments are never deleted: a wrong entry is marked as an
 * error with a reason, so every shilling stays accounted for.
 */
class PaymentService
{
    public function __construct(
        protected SystemSettingsService $settings,
        protected StudentDocumentService $documents,
        protected AlertRecipients $recipients,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated PaymentRequest data
     */
    public function record(Fee $fee, array $data, ?UploadedFile $receipt, User $by): Payment
    {
        if (! $fee->isApproved()) {
            throw ValidationException::withMessages([
                'amount' => 'This scholarship hasn\'t been approved yet, so no payment can be recorded.',
            ]);
        }

        $overLimit = $this->settings->paymentNeedsApproval($data['amount']);
        $waits = $overLimit && ! $by->can('approve payments');

        $payment = DB::transaction(fn () => Payment::create([
            'fee_id' => $fee->id,
            'amount' => $data['amount'],
            'paid_on' => $data['paid_on'],
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'receipt_document_id' => $receipt
                ? $this->documents->store($fee->student, $receipt, DocumentType::Receipt, $by)->id
                : null,
            'status' => $waits ? PaymentStatus::PendingApproval : PaymentStatus::Paid,
            'recorded_by' => $by->id,
            // An Admin recording an over-limit payment approves it in the same step.
            'approved_by' => $overLimit && ! $waits ? $by->id : null,
            'approved_at' => $overLimit && ! $waits ? now() : null,
        ]));

        if ($waits) {
            Notification::send(
                $this->recipients->withPermission('approve payments', except: $by),
                new PaymentAwaitingApproval($payment->load('fee.student', 'recorder')),
            );
        }

        return $payment;
    }

    public function approve(Payment $payment, User $admin): void
    {
        $this->ensurePending($payment);

        $payment->update([
            'status' => PaymentStatus::Paid,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $this->tellRecorder($payment, true, $admin);
    }

    public function reject(Payment $payment, User $admin, string $reason): void
    {
        $this->ensurePending($payment);

        $payment->update([
            'status' => PaymentStatus::Rejected,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->tellRecorder($payment, false, $admin, $reason);
    }

    public function markError(Payment $payment, User $by, string $reason): void
    {
        if (! $payment->status->canBeMarkedAsError()) {
            throw ValidationException::withMessages(['reason' => 'This payment is already '.strtolower($payment->status->label()).'.']);
        }

        $payment->update([
            'status' => PaymentStatus::Error,
            'error_reason' => $reason,
            'error_marked_by' => $by->id,
            'error_marked_at' => now(),
        ]);
    }

    protected function ensurePending(Payment $payment): void
    {
        if ($payment->status !== PaymentStatus::PendingApproval) {
            throw ValidationException::withMessages(['payment' => 'This payment has already been decided.']);
        }
    }

    protected function tellRecorder(Payment $payment, bool $approved, User $admin, ?string $reason = null): void
    {
        $payment->loadMissing('recorder', 'fee.student');

        if (! $payment->recorder || $payment->recorder->is($admin)) {
            return;
        }

        $currency = $this->settings->get()->currency;
        $payment->recorder->notify(new ApprovalDecided(
            "The payment of {$currency} ".number_format((float) $payment->amount, 2)." for {$payment->fee->student->full_name}",
            $approved,
            $admin->name,
            $reason,
            $payment->fee->student_id,
        ));
    }
}
