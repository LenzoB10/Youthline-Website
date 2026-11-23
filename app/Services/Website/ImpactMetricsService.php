<?php

namespace App\Services\Website;

use App\Models\AcademicResult;
use App\Models\AlumniOutcome;
use App\Models\Business;
use App\Models\FamilySupport;
use App\Models\Household;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Support\Facades\Cache;

/**
 * Calculates the public impact numbers live from the records managers
 * enter. Only ever returns counts — never names or any personal data.
 */
class ImpactMetricsService
{
    protected const CACHE_KEY = 'public.impact_metrics';

    /**
     * Headline figures for the Home page impact strip.
     *
     * @return array<int, array{key: string, label: string, value: int}>
     */
    public function headline(): array
    {
        $raw = $this->raw();

        return [
            ['key' => 'supported', 'label' => 'Young people supported', 'value' => $raw['supported']],
            ['key' => 'in_school', 'label' => 'In school right now', 'value' => $raw['active_secondary'] + $raw['active_university']],
            ['key' => 'graduates', 'label' => 'Graduates', 'value' => $raw['graduates']],
            ['key' => 'families', 'label' => 'Families reached', 'value' => $raw['families']],
        ];
    }

    /**
     * The full set of impact figures (used by the Admin "Impact summary" report).
     *
     * @return array<int, array{key: string, label: string, value: int}>
     */
    public function all(): array
    {
        $raw = $this->raw();

        return [
            ['key' => 'supported', 'label' => 'Young people supported', 'value' => $raw['supported']],
            ['key' => 'active_secondary', 'label' => 'Active in secondary school', 'value' => $raw['active_secondary']],
            ['key' => 'active_university', 'label' => 'Active in college or university', 'value' => $raw['active_university']],
            ['key' => 'graduates', 'label' => 'Graduates', 'value' => $raw['graduates']],
            ['key' => 'msce', 'label' => 'Sat their MSCE exams', 'value' => $raw['msce']],
            ['key' => 'families', 'label' => 'Families reached through home visits', 'value' => $raw['families']],
            ['key' => 'family_support', 'label' => 'Families given direct support', 'value' => $raw['family_support']],
            ['key' => 'businesses', 'label' => 'Youth businesses started', 'value' => $raw['businesses']],
            ['key' => 'alumni_active', 'label' => 'Alumni employed or studying', 'value' => $raw['alumni_active']],
        ];
    }

    /**
     * Number of students linked to each program, keyed by program id.
     *
     * @return array<int, int>
     */
    public function programStudentCounts(): array
    {
        return $this->raw()['programs'];
    }

    /**
     * Compute (and cache) the raw figures from live records.
     *
     * @return array<string, mixed>
     */
    protected function raw(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(15), function (): array {
            return [
                'supported' => Student::query()->where('status', '!=', 'applicant')->count(),
                'active_secondary' => $this->activeAtLevel('form%'),
                'active_university' => $this->activeAtLevel('year%'),
                'graduates' => Student::query()->where('status', 'graduated')->count(),
                'msce' => AcademicResult::query()->where('exam_type', 'msce')->distinct()->count('student_id'),
                'families' => Household::query()->whereHas('homeVisits')->count(),
                'family_support' => FamilySupport::query()->distinct()->count('household_id'),
                'businesses' => Business::query()->count(),
                'alumni_active' => AlumniOutcome::query()
                    ->whereIn('outcome', ['employed', 'further_study'])
                    ->distinct()
                    ->count('student_id'),
                'programs' => Program::query()
                    ->withCount('students')
                    ->get()
                    ->mapWithKeys(fn (Program $program): array => [$program->id => $program->students_count])
                    ->all(),
            ];
        });
    }

    /**
     * Count active students whose current-year enrolment is at the given level.
     */
    protected function activeAtLevel(string $levelPattern): int
    {
        return Student::query()
            ->where('status', 'active')
            ->whereHas('enrollments', function ($query) use ($levelPattern): void {
                $query->where('level', 'like', $levelPattern)
                    ->whereHas('academicYear', fn ($year) => $year->where('is_current', true));
            })
            ->count();
    }

    /**
     * Clear the cached figures (call after records change, if needed).
     */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
