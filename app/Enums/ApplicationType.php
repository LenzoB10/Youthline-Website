<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationType: string
{
    use HasOptions;

    case University = 'university';
    case College = 'college';
    case Scholarship = 'scholarship';
    case Job = 'job';

    public function label(): string
    {
        return match ($this) {
            self::University => 'University',
            self::College => 'College',
            self::Scholarship => 'Scholarship',
            self::Job => 'Job',
        };
    }
}
