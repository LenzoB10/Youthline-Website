<?php

namespace App\Services\Support;

use App\Enums\StudentStatus;
use App\Models\HomeVisit;
use App\Models\Household;
use App\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HomeVisitService
{
    public function __construct(
        protected SystemSettingsService $settings,
        protected PhotoService $photos,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated HomeVisitRequest data
     * @param  array<int, UploadedFile>  $photos
     */
    public function record(Household $household, array $data, array $photos, User $by): HomeVisit
    {
        // Photos of a family's home need a guardian's photo consent.
        if ($photos && ! $household->photoConsentGiven()) {
            throw ValidationException::withMessages([
                'photos' => 'No guardian in this household has given photo consent, so photos can\'t be added. Record the consent first (Documents & consent).',
            ]);
        }

        return DB::transaction(function () use ($household, $data, $photos, $by) {
            $visit = HomeVisit::create([
                'household_id' => $household->id,
                'visit_date' => $data['visit_date'],
                'members_count' => $data['members_count'] ?? null,
                'living_conditions' => $data['living_conditions'] ?? null,
                'income_sources' => $data['income_sources'] ?? null,
                'needs_identified' => $data['needs_identified'] ?? null,
                'next_visit_due' => $data['next_visit_due']
                    ?? Carbon::parse($data['visit_date'])->addMonths($this->settings->homeVisitIntervalMonths())->toDateString(),
                'recorded_by' => $by->id,
            ]);

            // Keep the household's headcount current.
            if (filled($data['members_count'] ?? null)) {
                $household->update(['members_count' => $data['members_count']]);
            }

            $this->photos->storeMany($visit, $photos, 'visit', $by);

            return $visit;
        });
    }

    public function delete(HomeVisit $visit): void
    {
        DB::transaction(function () use ($visit) {
            $this->photos->deleteAllFor($visit);
            $visit->delete();
        });
    }

    /**
     * Households with an active student that were never visited, or whose next
     * visit falls within the coming two weeks (or has passed).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function dueHouseholds(int $withinDays = 14): Collection
    {
        $cutoff = now()->addDays($withinDays)->endOfDay();

        return Household::query()
            ->whereHas('students', fn ($q) => $q->where('status', StudentStatus::Active->value))
            ->with(['latestVisit', 'students' => fn ($q) => $q->where('status', StudentStatus::Active->value)])
            ->get()
            ->filter(fn (Household $h) => ! $h->latestVisit || ! $h->latestVisit->next_visit_due || $h->latestVisit->next_visit_due->lte($cutoff))
            ->sortBy(fn (Household $h) => $h->latestVisit?->next_visit_due?->timestamp ?? 0)
            ->map(fn (Household $h) => [
                'household_id' => $h->id,
                'household' => $h->head_name ?: 'Unnamed household',
                'area' => collect([$h->village_area, $h->district])->filter()->join(', '),
                'student_id' => $h->students->first()?->id,
                'students' => $h->students->map->full_name->values()->all(),
                'last_visit' => $h->latestVisit?->visit_date?->toDateString(),
                'next_visit_due' => $h->latestVisit?->next_visit_due?->toDateString(),
                'is_overdue' => ! $h->latestVisit || ($h->latestVisit->next_visit_due?->isPast() ?? true),
            ])
            ->values();
    }
}
