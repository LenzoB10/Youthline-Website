<?php

namespace App\Services\Settings;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Organisation-wide approval limits and policy toggles (plan section 2.1).
 * Later phases ask this service, e.g. whether a payment needs Admin approval.
 */
class SystemSettingsService
{
    protected const CACHE_KEY = 'system.settings';

    public function get(): SystemSetting
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => $this->row());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, User $user): SystemSetting
    {
        $settings = $this->row();
        $settings->update([...$data, 'updated_by' => $user->getKey()]);

        Cache::forget(self::CACHE_KEY);

        return $settings;
    }

    public function scholarshipNeedsApproval(): bool
    {
        return $this->get()->require_scholarship_approval;
    }

    /**
     * Percentage points a term average must fall by to raise a grade-drop alert.
     */
    public function gradeDropThreshold(): float
    {
        return (float) ($this->get()->grade_drop_threshold ?? 10);
    }

    /**
     * A student with no mentorship session in this many days is overdue.
     */
    public function mentorshipOverdueDays(): int
    {
        return (int) ($this->get()->mentorship_overdue_days ?? 60);
    }

    /**
     * Default gap before a family's next home visit.
     */
    public function homeVisitIntervalMonths(): int
    {
        return (int) ($this->get()->home_visit_interval_months ?? 12);
    }

    /**
     * A running business is due a check-in after this many days.
     */
    public function businessCheckinDays(): int
    {
        return (int) ($this->get()->business_checkin_days ?? 90);
    }

    /**
     * Who may read mentorship/counselling notes besides their author and the Admin.
     */
    public function sessionNotesVisibleToAllManagers(): bool
    {
        return $this->get()->counselling_notes_visibility === 'all_managers';
    }

    /**
     * The settings row, created on first use. A freshly created row is reloaded so the
     * database defaults (approvals ON, default limits) are present, never nulls.
     */
    protected function row(): SystemSetting
    {
        return SystemSetting::query()->first() ?? SystemSetting::query()->create([])->refresh();
    }
}
