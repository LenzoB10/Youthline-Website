<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RepairStatus: string
{
    use HasOptions;

    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
        };
    }

    /**
     * Repairs only move forward: Planned → In progress → Completed.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Planned => self::InProgress,
            self::InProgress => self::Completed,
            self::Completed => null,
        };
    }
}
