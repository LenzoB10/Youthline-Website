<?php

namespace App\Enums\Concerns;

/**
 * For string-backed enums with a label(): the [{value, label}] list used by selects.
 */
trait HasOptions
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
