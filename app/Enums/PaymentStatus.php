<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Paid = 'paid';
    case PendingApproval = 'pending_approval';
    case Rejected = 'rejected';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Paid',
            self::PendingApproval => 'Awaiting approval',
            self::Rejected => 'Rejected',
            self::Error => 'Marked as error',
        };
    }

    /**
     * Only a paid payment reduces what Youthline still owes.
     */
    public function countsTowardsBalance(): bool
    {
        return $this === self::Paid;
    }

    /**
     * A recorded payment can be marked as an error; one already rejected or in error cannot.
     */
    public function canBeMarkedAsError(): bool
    {
        return in_array($this, [self::Paid, self::PendingApproval], true);
    }
}
