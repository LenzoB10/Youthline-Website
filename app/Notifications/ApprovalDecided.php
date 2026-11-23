<?php

namespace App\Notifications;

use App\Notifications\Concerns\InAppAlert;
use Illuminate\Notifications\Notification;

/**
 * Tells the manager who recorded a scholarship what the Admin decided.
 */
class ApprovalDecided extends Notification
{
    use InAppAlert;

    /**
     * @param  string  $subject  e.g. "Payment of MWK 600,000.00 for Thandiwe Banda"
     */
    public function __construct(
        public string $subject,
        public bool $approved,
        public string $decidedBy,
        public ?string $reason,
        public string $link,
    ) {}

    protected function title(): string
    {
        return $this->approved ? 'Approved' : 'Not approved';
    }

    protected function message(): string
    {
        $message = sprintf(
            '%s was <strong>%s</strong> by %s.',
            e($this->subject),
            $this->approved ? 'approved' : 'rejected',
            e($this->decidedBy),
        );

        if (! $this->approved && filled($this->reason)) {
            $message .= ' Reason: '.e($this->reason);
        }

        return $message;
    }

    protected function url(): ?string
    {
        return $this->link;
    }

    protected function kind(): string
    {
        return $this->approved ? 'approved' : 'rejected';
    }
}
