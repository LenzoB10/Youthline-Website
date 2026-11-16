<?php

namespace App\Contracts\Students;

use App\Enums\EducationLevel;

/**
 * Decides which level a student moves to when promoted.
 *
 * The promotion code depends on this contract, not on a concrete rule, so the
 * rule can change (e.g. a 6-year degree, or skipping a level) by binding a new
 * implementation — without touching the planner or the executor.
 */
interface LevelProgression
{
    /**
     * The next level, or null when the student is at the top of a stage
     * (Form 4, Year 5) and the next step is a human decision.
     */
    public function next(EducationLevel $level): ?EducationLevel;
}
