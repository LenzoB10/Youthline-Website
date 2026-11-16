<?php

namespace App\Notifications;

use App\Notifications\Concerns\InAppAlert;
use Illuminate\Notifications\Notification;

/**
 * Tells the manager who recorded a payment or scholarship what the Admin decided.
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
        public int $studentId,
        public string $anchor = '#academics',
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
        return route('students.show', $this->studentId).$this->anchor;
    }

    protected function kind(): string
    {
        return $this->approved ? 'approved' : 'rejected';
    }
}
