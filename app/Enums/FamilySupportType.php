<?php

namespace App\Enums;

/**
 * The kinds of support Youthline gives a family.
 */
enum FamilySupportType: string
{
    case Money = 'money';
    case Food = 'food';
    case HouseholdItems = 'household_items';
    case HomeRepair = 'home_repair';
    case SchoolItems = 'school_items';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Money => 'Money',
            self::Food => 'Food',
            self::HouseholdItems => 'Household items',
            self::HomeRepair => 'Home repair',
            self::SchoolItems => 'School items',
            self::Other => 'Other',
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
