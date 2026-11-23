<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Paid = 'paid';
    case Rejected = 'rejected';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Paid',
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
     * Only a paid payment can be marked as an error. (Rejected payments are kept
     * from before payment approvals were removed.)
     */
    public function canBeMarkedAsError(): bool
    {
        return $this === self::Paid;
    }
}
