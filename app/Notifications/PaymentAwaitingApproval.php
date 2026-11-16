<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Notifications\Concerns\InAppAlert;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Notifications\Notification;

class PaymentAwaitingApproval extends Notification
{
    use InAppAlert;

    public function __construct(public Payment $payment) {}

    protected function title(): string
    {
        return 'Payment awaiting your approval';
    }

    protected function message(): string
    {
        $currency = app(SystemSettingsService::class)->get()->currency;

        return sprintf(
            '%s recorded a payment of <strong>%s %s</strong> for %s. It is over the approval limit.',
            e($this->payment->recorder?->name ?? 'A manager'),
            e($currency),
            e(number_format((float) $this->payment->amount, 2)),
            e($this->payment->fee->student->full_name),
        );
    }

    protected function url(): ?string
    {
        return route('finance.approvals.index');
    }

    protected function kind(): string
    {
        return 'payment_approval';
    }
}
