<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single row of organisation-wide policy: approval limits and toggles
 * (Development Plan section 2.1). Read it through SystemSettingsService.
 */
class SystemSetting extends Model
{
    use LogsActivityTrait;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'require_payment_approval' => 'boolean',
            'payment_approval_limit' => 'decimal:2',
            'require_scholarship_approval' => 'boolean',
            'require_repair_approval' => 'boolean',
            'repair_approval_limit' => 'decimal:2',
            'grade_drop_threshold' => 'decimal:2',
            'mentorship_overdue_days' => 'integer',
            'home_visit_interval_months' => 'integer',
            'business_checkin_days' => 'integer',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
