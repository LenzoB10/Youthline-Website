<?php

use App\Models\AcademicYear;
use App\Models\ExitReason;
use App\Models\SystemSetting;
use App\Models\Term;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SettingsSeeder;

it('seeds a fresh database with Phase 1 reference data', function () {
    $this->seed(DatabaseSeeder::class);

    expect(ExitReason::query()->count())->toBe(9)
        ->and(SystemSetting::query()->count())->toBe(1)
        ->and(SystemSetting::first()->require_scholarship_approval)->toBeTrue()
        ->and(AcademicYear::query()->where('is_current', true)->pluck('name')->all())->toBe(['2025'])
        ->and(Term::query()->where('is_current', true)->count())->toBe(1);
});

it('can re-run the settings seeder without duplicating or overwriting edits', function () {
    $this->seed(SettingsSeeder::class);
    SystemSetting::first()->update(['grade_drop_threshold' => 7]);
    ExitReason::query()->where('name', 'Other')->update(['is_active' => false]);

    $this->seed(SettingsSeeder::class);

    expect(ExitReason::query()->count())->toBe(9)
        ->and(SystemSetting::query()->count())->toBe(1)
        ->and((float) SystemSetting::first()->grade_drop_threshold)->toBe(7.0)
        ->and(ExitReason::query()->where('name', 'Other')->value('is_active'))->toBeFalsy();
});
