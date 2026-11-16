<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum SessionType: string
{
    use HasOptions;

    case Mentorship = 'mentorship';
    case Counselling = 'counselling';

    public function label(): string
    {
        return match ($this) {
            self::Mentorship => 'Mentorship',
            self::Counselling => 'Counselling',
        };
    }
}
