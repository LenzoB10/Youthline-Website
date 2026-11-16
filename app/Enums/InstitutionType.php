<?php

namespace App\Enums;

enum InstitutionType: string
{
    case Secondary = 'secondary';
    case College = 'college';
    case University = 'university';

    public function label(): string
    {
        return match ($this) {
            self::Secondary => 'Secondary school',
            self::College => 'College',
            self::University => 'University',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type) => ['value' => $type->value, 'label' => $type->label()], self::cases());
    }
}
