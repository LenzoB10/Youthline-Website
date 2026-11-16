<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum FundingType: string
{
    use HasOptions;

    case Grant = 'grant';
    case Loan = 'loan';

    public function label(): string
    {
        return match ($this) {
            self::Grant => 'Grant (not repaid)',
            self::Loan => 'Loan (repaid)',
        };
    }
}
