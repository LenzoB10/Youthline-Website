<?php

namespace App\Enums;

enum ResultType: string
{
    case Term = 'term';
    case Msce = 'msce';
    case University = 'university';

    public function label(): string
    {
        return match ($this) {
            self::Term => 'Term exam',
            self::Msce => 'MSCE',
            self::University => 'University',
        };
    }

    /**
     * Term and university results are an average out of 100; MSCE is aggregate points.
     */
    public function usesAverage(): bool
    {
        return $this !== self::Msce;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type) => ['value' => $type->value, 'label' => $type->label()], self::cases());
    }
}
