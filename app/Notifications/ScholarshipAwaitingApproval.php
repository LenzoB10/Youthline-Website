<?php

namespace App\Notifications;

use App\Models\Fee;
use App\Notifications\Concerns\InAppAlert;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Notifications\Notification;

class ScholarshipAwaitingApproval extends Notification
{
    use InAppAlert;

    public function __construct(public Fee $fee) {}

    protected function title(): string
    {
        return 'New scholarship awaiting your approval';
    }

    protected function message(): string
    {
        $currency = app(SystemSettingsService::class)->get()->currency;

        return sprintf(
            '%s added the first fees for <strong>%s</strong>: Youthline would pay %s %s for %s.',
            e($this->fee->creator?->name ?? 'A manager'),
            e($this->fee->student->full_name),
            e($currency),
            e(number_format((float) $this->fee->youthline_share, 2)),
            e(trim(($this->fee->term?->academicYear?->name ?? '').' '.($this->fee->term?->name ?? ''))),
        );
    }

    protected function url(): ?string
    {
        return route('finance.approvals.index');
    }

    protected function kind(): string
    {
        return 'scholarship_approval';
    }
}
