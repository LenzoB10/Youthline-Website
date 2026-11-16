<?php

namespace App\Models;

use App\Enums\FeeApproval;
use App\Enums\RepairStatus;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RepairProject extends Model
{
    use HasFactory, LogsActivityTrait;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'status' => RepairStatus::class,
            // Same three states as a scholarship: approved, pending, rejected.
            'approval_status' => FeeApproval::class,
            'approved_at' => 'datetime',
            'started_on' => 'date',
            'completed_on' => 'date',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable');
    }

    public function isApproved(): bool
    {
        return $this->approval_status === FeeApproval::Approved;
    }
}
