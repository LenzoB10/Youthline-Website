<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ExitReason;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

/**
 * Phase 1 reference data: standard exit reasons and the default approval policy.
 * Safe to re-run on an existing database — it never duplicates or overwrites edits.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Completed studies', 'Finished their programme of study.'],
            ['Family relocated', 'The family moved out of the area Youthline serves.'],
            ['Financial or family hardship', 'Left school because of hardship at home.'],
            ['Pregnancy or early marriage', 'Left school because of pregnancy or early marriage.'],
            ['Found employment', 'Left to take up work.'],
            ['Health reasons', 'Long-term illness or disability.'],
            ['Transferred to another sponsor', 'Now supported by another organisation.'],
            ['Lost contact', 'Youthline can no longer reach the student or family.'],
            ['Other', 'Any other reason — add details on the student record.'],
        ] as $i => [$name, $description]) {
            ExitReason::query()->firstOrCreate(
                ['name' => $name],
                ['description' => $description, 'display_order' => $i + 1],
            );
        }

        // Plan section 2.1 defaults: approvals on, limits the Admin can change later.
        if (! SystemSetting::query()->exists()) {
            SystemSetting::query()->create([
                'currency' => 'MWK',
                'require_scholarship_approval' => true,
                'counselling_notes_visibility' => 'author',
            ]);
        }

        // If a current year exists but no current term, default to its latest term.
        $year = AcademicYear::query()->where('is_current', true)->first();
        if ($year && ! $year->terms()->where('is_current', true)->exists()) {
            $year->terms()->orderByDesc('start_date')->first()?->update(['is_current' => true]);
        }
    }
}
