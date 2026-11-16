<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AlumniOutcomeType: string
{
    use HasOptions;

    case Employed = 'employed';
    case FurtherStudy = 'further_study';
    case Business = 'business';
    case Seeking = 'seeking';

    public function label(): string
    {
        return match ($this) {
            self::Employed => 'Employed',
            self::FurtherStudy => 'Further study',
            self::Business => 'Running a business',
            self::Seeking => 'Still looking',
        };
    }
}
