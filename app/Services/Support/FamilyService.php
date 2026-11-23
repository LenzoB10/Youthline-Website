<?php

namespace App\Services\Support;

use App\Models\FamilySupport;
use App\Models\Household;
use App\Models\Student;
use App\Models\User;

/**
 * Families Youthline supports: students' homes and community families alike.
 */
class FamilyService
{
    public function __construct(protected HomeVisitService $visits) {}

    /**
     * Add a family directly (a community family, or a student's home before the
     * student is registered).
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $by): Household
    {
        return Household::create([...$data, 'is_supported' => $data['is_supported'] ?? true, 'created_by' => $by->id]);
    }

    /**
     * Everything the Families page shows.
     *
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $year = (int) now()->format('Y');
        $recent = FamilySupport::query()->with('household:id,head_name')->latest('given_on')->latest('id')->limit(15)->get();

        return [
            'households' => Household::query()
                ->with(['students:id,household_id,first_name,last_name,status', 'latestVisit'])
                ->withSum('familySupports as support_total', 'amount')
                ->orderByDesc('is_supported')
                ->orderBy('head_name')
                ->get()
                ->map(fn (Household $h) => [
                    'id' => $h->id,
                    'name' => $h->displayName(),
                    'area' => collect([$h->village_area, $h->district])->filter()->join(', '),
                    'kind' => $h->students->isNotEmpty() ? 'student' : 'community',
                    'students' => $h->students->map(fn (Student $s) => $s->full_name)->values()->all(),
                    'is_supported' => $h->is_supported,
                    'last_visit' => $h->latestVisit?->visit_date?->toDateString(),
                    'next_visit_due' => $h->latestVisit?->next_visit_due?->toDateString(),
                    'support_total' => round((float) $h->support_total, 2),
                ]),
            'due' => $this->visits->dueHouseholds(),
            'supportTotals' => [
                'year' => $year,
                'given' => round((float) FamilySupport::query()->whereYear('given_on', $year)->sum('amount'), 2),
                'entries' => FamilySupport::query()->whereYear('given_on', $year)->count(),
                'families' => FamilySupport::query()->whereYear('given_on', $year)->distinct()->count('household_id'),
            ],
            'recentSupport' => $recent->map(fn (FamilySupport $f) => [
                'id' => $f->id,
                'household' => $f->household?->displayName() ?? 'Unnamed family',
                'household_id' => $f->household_id,
                'given_on' => $f->given_on?->toDateString(),
                'type_label' => $f->type->label(),
                'description' => $f->description,
                'amount' => $f->amount !== null ? (float) $f->amount : null,
            ]),
        ];
    }
}
