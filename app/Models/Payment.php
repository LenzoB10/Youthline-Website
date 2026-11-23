<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, LogsActivityTrait;

    /**
     * How Youthline pays schools.
     */
    public const METHODS = ['Bank transfer', 'Mobile money', 'Cheque', 'Cash'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_on' => 'date',
            'status' => PaymentStatus::class,
            'approved_at' => 'datetime',
            'error_marked_at' => 'datetime',
        ];
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'receipt_document_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function errorMarker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'error_marked_by');
    }
}
