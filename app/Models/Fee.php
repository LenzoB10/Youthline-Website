<?php

namespace App\Models;

use App\Enums\FeeApproval;
use App\Enums\PaymentStatus;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fee extends Model
{
    use HasFactory, LogsActivityTrait;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount_owed' => 'decimal:2',
            'youthline_share' => 'decimal:2',
            'approval_status' => FeeApproval::class,
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Fees Youthline has agreed to pay (rejected or pending scholarships are not owed yet).
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', FeeApproval::Approved->value);
    }

    /**
     * Adds paid_total and pending_total (sums of this fee's payments by status).
     */
    public function scopeWithPaymentTotals(Builder $query): Builder
    {
        return $query
            ->withSum(['payments as paid_total' => fn ($q) => $q->where('status', PaymentStatus::Paid->value)], 'amount')
            ->withSum(['payments as pending_total' => fn ($q) => $q->where('status', PaymentStatus::PendingApproval->value)], 'amount');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidTotal(): float
    {
        return (float) ($this->paid_total
            ?? $this->payments()->where('status', PaymentStatus::Paid->value)->sum('amount'));
    }

    public function pendingTotal(): float
    {
        return (float) ($this->pending_total
            ?? $this->payments()->where('status', PaymentStatus::PendingApproval->value)->sum('amount'));
    }

    /**
     * What Youthline still has to pay for this term. Negative means overpaid.
     */
    public function balance(): float
    {
        return round((float) $this->youthline_share - $this->paidTotal(), 2);
    }

    /**
     * Room left for new payments, counting ones still waiting for approval.
     */
    public function remainingToRecord(): float
    {
        return round($this->balance() - $this->pendingTotal(), 2);
    }

    public function isApproved(): bool
    {
        return $this->approval_status === FeeApproval::Approved;
    }

    /**
     * unpaid | part_paid | paid | overpaid | pending_approval | rejected
     */
    public function paymentState(): string
    {
        if (! $this->isApproved()) {
            return $this->approval_status === FeeApproval::Pending ? 'pending_approval' : 'rejected';
        }

        $balance = $this->balance();

        return match (true) {
            $balance < 0 => 'overpaid',
            $balance == 0 => 'paid',
            $this->paidTotal() > 0 => 'part_paid',
            default => 'unpaid',
        };
    }
}
