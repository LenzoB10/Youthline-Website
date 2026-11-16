<?php

namespace App\Http\Controllers\Sponsors;

use App\Enums\EducationLevel;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sponsors\SponsorRequest;
use App\Models\Sponsor;
use App\Models\Sponsorship;
use App\Models\Student;
use App\Services\Settings\AcademicCalendarService;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:manage sponsors', except: ['destroy'])]
#[Middleware('permission:delete sponsors', only: ['destroy'])]
class SponsorController extends Controller
{
    public function __construct(
        protected SystemSettingsService $settings,
        protected AcademicCalendarService $calendar,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Sponsors/Index', [
            'currency' => $this->settings->get()->currency,
            'types' => collect(Sponsor::TYPES)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'sponsors' => Sponsor::query()
                ->withCount('activeSponsorships as students_count')
                ->withSum('activeSponsorships as yearly_total', 'amount_per_year')
                ->orderBy('name')
                ->get()
                ->map(fn (Sponsor $s) => [
                    ...$s->only(['id', 'name', 'type', 'email', 'phone', 'students_count']),
                    'type_label' => Sponsor::TYPES[$s->type] ?? $s->type,
                    'yearly_total' => (float) $s->yearly_total,
                ]),
        ]);
    }

    public function store(SponsorRequest $request): RedirectResponse
    {
        $sponsor = Sponsor::create($request->validated());

        return redirect()->route('sponsors.show', $sponsor)->with('success', 'Sponsor added. Now link the students they support.');
    }

    public function show(Sponsor $sponsor): Response
    {
        $yearId = $this->calendar->current()?->id;
        $sponsor->load(['sponsorships' => fn ($q) => $q->with([
            'student.enrollments' => fn ($e) => $e->where('academic_year_id', $yearId)->with('institution'),
            'updateAuthor:id,name',
        ])->latest('start_date')]);
        $linked = $sponsor->sponsorships->filter->isActive()->pluck('student_id');

        return Inertia::render('Sponsors/Show', [
            'currency' => $this->settings->get()->currency,
            'types' => collect(Sponsor::TYPES)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'sponsor' => [
                ...$sponsor->only(['id', 'name', 'type', 'email', 'phone', 'notes']),
                'type_label' => Sponsor::TYPES[$sponsor->type] ?? $sponsor->type,
            ],
            'sponsorships' => $sponsor->sponsorships->map(fn (Sponsorship $s) => [
                'id' => $s->id,
                'student_id' => $s->student_id,
                'student' => $s->student?->full_name,
                'status' => $s->student?->status->label(),
                'level' => EducationLevel::tryFrom((string) $s->student?->enrollments->first()?->level)?->label(),
                'school' => $s->student?->enrollments->first()?->institution?->name,
                'start_date' => $s->start_date?->toDateString(),
                'end_date' => $s->end_date?->toDateString(),
                'amount_per_year' => $s->amount_per_year !== null ? (float) $s->amount_per_year : null,
                'is_active' => $s->isActive(),
                'progress_update' => $s->progress_update,
                'progress_update_on' => $s->progress_update_on?->toDateString(),
                'progress_update_by' => $s->updateAuthor?->name,
            ])->values(),
            'students' => Student::query()
                ->whereIn('status', [StudentStatus::Active->value, StudentStatus::Applicant->value, StudentStatus::Paused->value])
                ->whereNotIn('id', $linked)
                ->orderBy('last_name')->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (Student $s) => ['value' => $s->id, 'label' => $s->full_name]),
        ]);
    }

    public function update(SponsorRequest $request, Sponsor $sponsor): RedirectResponse
    {
        $sponsor->update($request->validated());

        return back()->with('success', 'Sponsor updated.');
    }

    public function destroy(Sponsor $sponsor): RedirectResponse
    {
        $sponsor->delete();

        return redirect()->route('sponsors.index')->with('success', 'Sponsor deleted.');
    }
}
