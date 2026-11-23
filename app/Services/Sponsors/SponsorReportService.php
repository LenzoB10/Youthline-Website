<?php

namespace App\Services\Sponsors;

use App\Enums\EducationLevel;
use App\Models\AcademicResult;
use App\Models\AcademicYear;
use App\Models\Fee;
use App\Models\Sponsor;
use App\Models\Sponsorship;
use App\Services\Settings\AcademicCalendarService;
use App\Services\Settings\SystemSettingsService;

/**
 * A sponsor's progress report, built from live data. It deliberately leaves out
 * anything private: counselling notes, home-visit findings, exit reasons, phones.
 */
class SponsorReportService
{
    public function __construct(
        protected AcademicCalendarService $calendar,
        protected SystemSettingsService $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Sponsor $sponsor): array
    {
        $year = $this->calendar->current();
        $sponsorships = $sponsor->activeSponsorships()
            ->with(['student', 'updateAuthor:id,name'])
            ->get()
            ->filter(fn (Sponsorship $s) => $s->student)
            ->sortBy(fn (Sponsorship $s) => $s->student->last_name);

        return [
            'sponsor' => ['name' => $sponsor->name, 'type' => Sponsor::TYPES[$sponsor->type] ?? $sponsor->type],
            'year' => $year?->name,
            'generated_on' => now()->toDateString(),
            'currency' => $this->settings->get()->currency,
            'students' => $sponsorships->map(fn (Sponsorship $s) => $this->student($s, $year))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function student(Sponsorship $sponsorship, ?AcademicYear $year): array
    {
        $yearId = $year?->id;
        $student = $sponsorship->student;
        $enrollment = $student->enrollments()->with('institution')
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->latest('id')->first();
        // "This year" = the current academic year (or the calendar year if none is set).
        $yearStart = $year?->start_date ?? now()->startOfYear();

        $results = $student->results()
            ->with('term.academicYear')
            ->get()
            ->sortByDesc(fn (AcademicResult $r) => $r->term?->start_date?->timestamp ?? 0)
            ->take(3)
            ->map(fn (AcademicResult $r) => [
                'term' => trim(($r->term?->academicYear?->name ?? '').' '.($r->term?->name ?? '')),
                'type' => $r->exam_type->label(),
                'score' => $r->exam_type->usesAverage()
                    ? rtrim(rtrim(number_format((float) $r->average, 1), '0'), '.').'%'
                    : $r->msce_points.' points',
            ])
            ->values()
            ->all();

        $fees = $student->fees()->approved()->withPaymentTotals()
            ->when($yearId, fn ($q) => $q->whereHas('term', fn ($t) => $t->where('academic_year_id', $yearId)))
            ->get();

        return [
            'name' => $student->full_name,
            'status' => $student->status->label(),
            'level' => EducationLevel::tryFrom((string) $enrollment?->level)?->label(),
            'school' => $enrollment?->institution?->name,
            'course' => $enrollment?->course,
            'since' => $sponsorship->start_date?->toDateString(),
            'amount_per_year' => $sponsorship->amount_per_year !== null ? (float) $sponsorship->amount_per_year : null,
            'results' => $results,
            'fees' => [
                'share' => round($fees->sum(fn (Fee $f) => (float) $f->youthline_share), 2),
                'paid' => round($fees->sum(fn (Fee $f) => $f->paidTotal()), 2),
            ],
            'support' => [
                'basic_needs' => round($student->distributions()->where('given_on', '>=', $yearStart)
                    ->with('itemCategory:id,unit_cost')->get()->sum(fn ($d) => $d->value()), 2),
                'sessions' => $student->mentorshipSessions()->where('session_date', '>=', $yearStart)->count(),
                'businesses' => $student->businesses()->get()
                    ->map(fn ($b) => $b->name.($b->is_running ? ' (running)' : ''))->all(),
            ],
            'update' => $sponsorship->progress_update,
            'update_on' => $sponsorship->progress_update_on?->toDateString(),
        ];
    }
}
