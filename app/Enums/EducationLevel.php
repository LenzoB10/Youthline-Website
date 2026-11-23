<?php

namespace App\Enums;

/**
 * The fixed education levels Youthline supports. Stored on enrollments.level.
 * Kept in code (not an editable list) because promotion depends on their order.
 */
enum EducationLevel: string
{
    case Form1 = 'form_1';
    case Form2 = 'form_2';
    case Form3 = 'form_3';
    case Form4 = 'form_4';
    case Year1 = 'year_1';
    case Year2 = 'year_2';
    case Year3 = 'year_3';
    case Year4 = 'year_4';
    case Year5 = 'year_5';

    public function label(): string
    {
        [$stage, $number] = explode('_', $this->value);

        return ($stage === 'form' ? 'Form ' : 'Year ').$number;
    }

    /**
     * Only students in the final years of university (Year 4 or Year 5) can graduate.
     */
    public function canGraduate(): bool
    {
        return in_array($this, [self::Year4, self::Year5], true);
    }

    public function stage(): string
    {
        return str_starts_with($this->value, 'form') ? 'secondary' : 'tertiary';
    }

    /**
     * The level a student moves to on promotion, or null at the top of a stage.
     * Form 4 → university is a separate decision, not an automatic promotion.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Form1 => self::Form2,
            self::Form2 => self::Form3,
            self::Form3 => self::Form4,
            self::Year1 => self::Year2,
            self::Year2 => self::Year3,
            self::Year3 => self::Year4,
            self::Year4 => self::Year5,
            self::Form4, self::Year5 => null,
        };
    }

    /**
     * @return array<int, array{value: string, label: string, stage: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $level) => [
            'value' => $level->value,
            'label' => $level->label(),
            'stage' => $level->stage(),
        ], self::cases());
    }
}
