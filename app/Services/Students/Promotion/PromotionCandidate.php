<?php

namespace App\Services\Students\Promotion;

use App\Enums\EducationLevel;
use App\Enums\PromotionDecision;

/**
 * One active student who has no record yet for the new academic year,
 * with what each possible decision would mean for them.
 */
final readonly class PromotionCandidate
{
    public function __construct(
        public int $studentId,
        public string $name,
        public ?EducationLevel $fromLevel,
        public ?EducationLevel $toLevel,
        public ?string $fromYear,
        public ?int $institutionId,
        public ?string $institution,
        public ?string $course,
    ) {}

    /**
     * Has an earlier level on record (otherwise it must be entered by hand).
     */
    public function isPlaced(): bool
    {
        return $this->fromLevel !== null;
    }

    public function canPromote(): bool
    {
        return $this->toLevel !== null;
    }

    public function allows(PromotionDecision $decision): bool
    {
        return match ($decision) {
            PromotionDecision::Promote => $this->canPromote(),
            PromotionDecision::Repeat => $this->isPlaced(),
        };
    }

    /**
     * The level the student will be at in the new year.
     */
    public function levelFor(PromotionDecision $decision): ?EducationLevel
    {
        return $decision === PromotionDecision::Promote ? $this->toLevel : $this->fromLevel;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'student_id' => $this->studentId,
            'name' => $this->name,
            'from_year' => $this->fromYear,
            'institution' => $this->institution,
            'from_level' => $this->fromLevel?->value,
            'from_label' => $this->fromLevel?->label(),
            'to_level' => $this->toLevel?->value,
            'to_label' => $this->toLevel?->label(),
            'can_promote' => $this->canPromote(),
        ];
    }
}
