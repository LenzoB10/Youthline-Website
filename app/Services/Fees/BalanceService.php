<?php

namespace App\Services\Fees;

use App\Models\Fee;
use Illuminate\Support\Collection;

/**
 * Outstanding fee balances: what Youthline has agreed to pay (approved fees)
 * minus what it has actually paid. Rejected payments or ones marked as an
 * error do not reduce a balance.
 */
class BalanceService
{
    /**
     * Approved fees with their paid totals, optionally for one term.
     *
     * @return Collection<int, Fee>
     */
    public function fees(?int $termId = null): Collection
    {
        return Fee::query()
            ->approved()
            ->withPaymentTotals()
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->with(['student:id,first_name,last_name,status', 'institution:id,name', 'term.academicYear'])
            ->get();
    }

    /**
     * One row per student who still has something to pay.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function unpaidByStudent(?int $termId = null): Collection
    {
        return $this->fees($termId)
            ->filter(fn (Fee $fee) => $fee->balance() > 0 && $fee->student)
            ->groupBy('student_id')
            ->map(function (Collection $fees) {
                $student = $fees->first()->student;

                return [
                    'student_id' => $student->id,
                    'student' => $student->full_name,
                    'student_status' => $student->status->label(),
                    'schools' => $fees->pluck('institution.name')->filter()->unique()->values()->all(),
                    'terms' => $fees->map(fn (Fee $fee) => $this->termLabel($fee))->unique()->values()->all(),
                    'share' => round($fees->sum(fn (Fee $fee) => (float) $fee->youthline_share), 2),
                    'paid' => round($fees->sum(fn (Fee $fee) => $fee->paidTotal()), 2),
                    'balance' => round($fees->sum(fn (Fee $fee) => $fee->balance()), 2),
                ];
            })
            ->sortByDesc('balance')
            ->values();
    }

    /**
     * One row per school with money still owed to it.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function unpaidBySchool(?int $termId = null): Collection
    {
        return $this->fees($termId)
            ->filter(fn (Fee $fee) => $fee->balance() > 0)
            ->groupBy(fn (Fee $fee) => $fee->institution_id ?? 0)
            ->map(fn (Collection $fees) => [
                'institution_id' => $fees->first()->institution_id,
                'school' => $fees->first()->institution?->name ?? 'School not recorded',
                'students' => $fees->pluck('student_id')->unique()->count(),
                'share' => round($fees->sum(fn (Fee $fee) => (float) $fee->youthline_share), 2),
                'paid' => round($fees->sum(fn (Fee $fee) => $fee->paidTotal()), 2),
                'balance' => round($fees->sum(fn (Fee $fee) => $fee->balance()), 2),
            ])
            ->sortByDesc('balance')
            ->values();
    }

    /**
     * @return array{outstanding: float, students: int, schools: int}
     */
    public function totals(?int $termId = null): array
    {
        $unpaid = $this->fees($termId)->filter(fn (Fee $fee) => $fee->balance() > 0);

        return [
            'outstanding' => round($unpaid->sum(fn (Fee $fee) => $fee->balance()), 2),
            'students' => $unpaid->pluck('student_id')->unique()->count(),
            'schools' => $unpaid->pluck('institution_id')->filter()->unique()->count(),
        ];
    }

    public function termLabel(Fee $fee): string
    {
        return trim(($fee->term?->academicYear?->name ?? '').' '.($fee->term?->name ?? ''));
    }
}
