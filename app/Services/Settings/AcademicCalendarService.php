<?php

namespace App\Services\Settings;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the calendar consistent: exactly one current academic year, and at most
 * one current term, which always belongs to the current year.
 */
class AcademicCalendarService
{
    public function current(): ?AcademicYear
    {
        return AcademicYear::query()->where('is_current', true)->first();
    }

    public function currentTerm(): ?Term
    {
        return Term::query()->where('is_current', true)->first();
    }

    public function makeYearCurrent(AcademicYear $year): void
    {
        DB::transaction(function () use ($year) {
            AcademicYear::query()->whereKeyNot($year->getKey())->where('is_current', true)->update(['is_current' => false]);
            // A current term must sit inside the current year.
            Term::query()->where('academic_year_id', '!=', $year->getKey())->where('is_current', true)->update(['is_current' => false]);

            if (! $year->is_current) {
                $year->update(['is_current' => true]);
            }
        });
    }

    public function makeTermCurrent(Term $term): void
    {
        DB::transaction(function () use ($term) {
            $this->makeYearCurrent($term->academicYear);

            Term::query()->whereKeyNot($term->getKey())->where('is_current', true)->update(['is_current' => false]);

            if (! $term->is_current) {
                $term->update(['is_current' => true]);
            }
        });
    }

    /**
     * Create a year; the first year ever created becomes current automatically.
     *
     * @param  array<string, mixed>  $data
     */
    public function createYear(array $data): AcademicYear
    {
        return DB::transaction(function () use ($data) {
            $makeCurrent = (bool) ($data['is_current'] ?? false) || ! AcademicYear::query()->exists();
            $year = AcademicYear::create([...$data, 'is_current' => false]);

            if ($makeCurrent) {
                $this->makeYearCurrent($year);
            }

            return $year;
        });
    }
}
