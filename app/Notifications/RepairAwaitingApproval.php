<?php

namespace App\Notifications;

use App\Models\RepairProject;
use App\Notifications\Concerns\InAppAlert;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Notifications\Notification;

class RepairAwaitingApproval extends Notification
{
    use InAppAlert;

    public function __construct(public RepairProject $repair) {}

    protected function title(): string
    {
        return 'Repair budget awaiting your approval';
    }

    protected function message(): string
    {
        $currency = app(SystemSettingsService::class)->get()->currency;

        return sprintf(
            '%s planned a repair for the <strong>%s</strong> household: %s, estimated at %s %s.',
            e($this->repair->creator?->name ?? 'A manager'),
            e($this->repair->household?->head_name ?? 'unnamed'),
            e(str($this->repair->description)->limit(80)),
            e($currency),
            e(number_format((float) $this->repair->estimated_cost, 2)),
        );
    }

    protected function url(): ?string
    {
        return route('finance.approvals.index');
    }

    protected function kind(): string
    {
        return 'repair_approval';
    }
}
