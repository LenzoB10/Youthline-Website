<?php

namespace App\Enums;

/**
 * Student lifecycle (System Guide, "Student statuses"). Students are never
 * deactivated or deleted by managers — they move between these statuses, and
 * leaving always needs a reason.
 */
enum StudentStatus: string
{
    case Applicant = 'applicant';
    case Active = 'active';
    case Graduated = 'graduated';
    case DroppedOut = 'dropped_out';
    case Transferred = 'transferred';
    case Paused = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::Applicant => 'Applicant',
            self::Active => 'Active',
            self::Graduated => 'Graduated',
            self::DroppedOut => 'Dropped out',
            self::Transferred => 'Transferred',
            self::Paused => 'Paused',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Applicant => 'Registered, waiting for assessment.',
            self::Active => 'Currently supported.',
            self::Graduated => 'Completed their studies.',
            self::DroppedOut => 'Left before finishing.',
            self::Transferred => 'Moved to another school or program.',
            self::Paused => 'Support temporarily on hold.',
        };
    }

    /**
     * Leaving (or pausing) always records a reason and a date.
     */
    public function requiresReason(): bool
    {
        return in_array($this, [self::Graduated, self::DroppedOut, self::Transferred, self::Paused], true);
    }

    /**
     * Permanent exits close the student's program memberships; a pause keeps them.
     */
    public function endsPrograms(): bool
    {
        return in_array($this, [self::Graduated, self::DroppedOut, self::Transferred], true);
    }

    /**
     * Where a student can move from this status.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Applicant => [self::Active, self::DroppedOut, self::Transferred],
            self::Active => [self::Graduated, self::DroppedOut, self::Transferred, self::Paused],
            // Any student who left can be re-admitted.
            self::Paused => [self::Active, self::Graduated, self::DroppedOut, self::Transferred],
            self::Graduated, self::DroppedOut, self::Transferred => [self::Active],
        };
    }

    public function canMoveTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * @return array<int, array{value: string, label: string, description: string, requires_reason: bool}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status) => [
            'value' => $status->value,
            'label' => $status->label(),
            'description' => $status->description(),
            'requires_reason' => $status->requiresReason(),
        ], self::cases());
    }
}
