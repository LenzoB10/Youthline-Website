<?php

namespace App\Services\Fees;

use App\Enums\DocumentType;
use App\Enums\PaymentStatus;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\User;
use App\Services\Students\StudentDocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The payment ledger. Payments are never deleted: a wrong entry is marked as an
 * error with a reason, so every shilling stays accounted for.
 */
class PaymentService
{
    public function __construct(protected StudentDocumentService $documents) {}

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

        return DB::transaction(fn () => Payment::create([
            'fee_id' => $fee->id,
            'amount' => $data['amount'],
            'paid_on' => $data['paid_on'],
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'receipt_document_id' => $receipt
                ? $this->documents->store($fee->student, $receipt, DocumentType::Receipt, $by)->id
                : null,
            // Every payment counts as soon as it is recorded — no approval step.
            'status' => PaymentStatus::Paid,
            'recorded_by' => $by->id,
        ]));
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
}
