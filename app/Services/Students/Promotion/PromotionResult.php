<?php

namespace App\Services\Students\Promotion;

final readonly class PromotionResult
{
    public function __construct(
        public int $promoted = 0,
        public int $repeating = 0,
    ) {}

    public function summary(): string
    {
        $parts = [];
        if ($this->promoted > 0) {
            $parts[] = "Promoted {$this->promoted} ".str('student')->plural($this->promoted);
        }
        if ($this->repeating > 0) {
            $parts[] = ($parts ? 'recorded ' : 'Recorded ')."{$this->repeating} repeating";
        }

        return $parts ? implode(' and ', $parts).'.' : 'Nothing was changed.';
    }
}
