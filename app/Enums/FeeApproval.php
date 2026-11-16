<?php

namespace App\Enums;

/**
 * Whether Youthline has agreed to fund a term's fees. A student's first fee is a
 * "new scholarship" and may need Admin sign-off (Settings → Approvals).
 */
enum FeeApproval: string
{
    case Approved = 'approved';
    case Pending = 'pending';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Approved => 'Approved',
            self::Pending => 'Awaiting approval',
            self::Rejected => 'Rejected',
        };
    }
}
