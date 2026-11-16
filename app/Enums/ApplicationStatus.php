<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationStatus: string
{
    use HasOptions;

    case Applied = 'applied';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Applied',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
        };
    }
}
