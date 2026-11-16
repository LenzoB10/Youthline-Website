<?php

namespace App\Services\Support;

use App\Models\Distribution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Basic needs: mostly money given to students to buy groceries.
 * Older item-based records (uniforms, books) are still counted, at their unit cost.
 */
class BasicNeedsService
{
    /**
     * @param  array{amount: float|string, given_on: string, note?: ?string}  $data
     */
    public function record(Student $student, array $data, User $by): Distribution
    {
        return Distribution::create([
            'student_id' => $student->id,
            'amount' => $data['amount'],
            'given_on' => $data['given_on'],
            'note' => $data['note'] ?? null,
            'quantity' => 1,
            'recorded_by' => $by->id,
        ]);
    }

    /**
     * The same amount given to many students at once (e.g. grocery money for
     * everyone in a program this month). Each student gets their own record.
     *
     * @param  array<int, int>  $studentIds
     * @return int how many students were recorded
     */
    public function groupHandout(float $amount, string $givenOn, ?string $note, array $studentIds, User $by): int
    {
        $now = now();
        $rows = collect($studentIds)->unique()->map(fn (int $id) => [
            'student_id' => $id,
            'amount' => $amount,
            'given_on' => $givenOn,
            'note' => $note,
            'quantity' => 1,
            'recorded_by' => $by->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::transaction(fn () => $rows->chunk(200)->each(fn ($chunk) => Distribution::query()->insert($chunk->values()->all())));

        return $rows->count();
    }

    /**
     * Money given in a year: total, students reached, and a month-by-month breakdown.
     *
     * @return array{total: float, students: int, records: int, months: array<int, array{month: string, total: float, students: int}>}
     */
    public function totals(?int $year = null): array
    {
        $year ??= (int) now()->format('Y');
        $records = Distribution::query()->with('itemCategory:id,unit_cost')->whereYear('given_on', $year)->get();

        return [
            'total' => round($records->sum(fn (Distribution $d) => $d->value()), 2),
            'students' => $records->pluck('student_id')->unique()->count(),
            'records' => $records->count(),
            'months' => $records
                ->groupBy(fn (Distribution $d) => $d->given_on->format('Y-m'))
                ->sortKeys()
                ->map(fn (Collection $rows, string $month) => [
                    'month' => $month,
                    'total' => round($rows->sum(fn (Distribution $d) => $d->value()), 2),
                    'students' => $rows->pluck('student_id')->unique()->count(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Money given to one student, optionally from a date.
     */
    public function studentTotal(Student $student, mixed $from = null): float
    {
        return round(
            $student->distributions()->with('itemCategory:id,unit_cost')
                ->when($from, fn ($q) => $q->where('given_on', '>=', $from))
                ->get()
                ->sum(fn (Distribution $d) => $d->value()),
            2,
        );
    }

    /**
     * Per-student totals for a year (for the report).
     *
     * @return Collection<int, array{student: string, total: float, times: int, last: ?string}>
     */
    public function perStudent(?int $year = null): Collection
    {
        $year ??= (int) now()->format('Y');

        return Distribution::query()
            ->with(['student:id,first_name,last_name', 'itemCategory:id,unit_cost'])
            ->whereYear('given_on', $year)
            ->get()
            ->groupBy('student_id')
            ->map(fn (Collection $rows) => [
                'student' => $rows->first()->student?->full_name ?? '—',
                'total' => round($rows->sum(fn (Distribution $d) => $d->value()), 2),
                'times' => $rows->count(),
                'last' => $rows->max('given_on')?->toDateString(),
            ])
            ->sortBy('student')
            ->values();
    }
}
