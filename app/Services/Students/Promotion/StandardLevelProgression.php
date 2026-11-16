<?php

namespace App\Services\Students\Promotion;

use App\Contracts\Students\LevelProgression;
use App\Enums\EducationLevel;

/**
 * Malawi's standard ladder: Form 1 → 4, Year 1 → 5. Form 4 → university is
 * never automatic (the student may graduate, repeat, or move to Year 1).
 */
class StandardLevelProgression implements LevelProgression
{
    public function next(EducationLevel $level): ?EducationLevel
    {
        return $level->next();
    }
}
