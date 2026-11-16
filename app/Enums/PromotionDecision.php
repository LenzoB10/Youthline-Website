<?php

namespace App\Enums;

/**
 * What happens to a student at the start of a new academic year.
 * Students left without a decision are simply skipped for now.
 */
enum PromotionDecision: string
{
    case Promote = 'promote';
    case Repeat = 'repeat';

    public function label(): string
    {
        return match ($this) {
            self::Promote => 'Promote',
            self::Repeat => 'Repeat',
        };
    }
}
