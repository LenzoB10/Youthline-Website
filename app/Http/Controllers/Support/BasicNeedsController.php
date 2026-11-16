<?php

namespace App\Http\Controllers\Support;

use App\Enums\EducationLevel;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\GroupHandoutRequest;
use App\Models\Distribution;
use App\Models\Program;
use App\Models\Student;
use App\Services\Settings\AcademicCalendarService;
use App\Services\Settings\SystemSettingsService;
use App\Services\Support\BasicNeedsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:manage basic needs')]
class BasicNeedsController extends Controller
{
    public function __construct(
        protected BasicNeedsService $basicNeeds,
        protected AcademicCalendarService $calendar,
        protected SystemSettingsService $settings,
    ) {}

    /**
     * Running totals plus the group-handout form.
     */
    public function index(Request $request): Response
    {
        $year = $request->integer('year') ?: (int) now()->format('Y');
        $currentYearId = $this->calendar->current()?->id;

        return Inertia::render('Support/BasicNeeds', [
            'year' => $year,
            'years' => Distribution::query()->whereNotNull('given_on')->pluck('given_on')
                ->map(fn ($d) => (int) $d->format('Y'))->push((int) now()->format('Y'))->unique()->sortDesc()->values(),
            'currency' => $this->settings->get()->currency,
            'totals' => $this->basicNeeds->totals($year),
            'levels' => EducationLevel::options(),
            'programs' => Program::query()->orderBy('display_order')->get(['id', 'name'])
                ->map(fn ($p) => ['value' => $p->id, 'label' => $p->name]),
            // Active students with what's needed to filter them by level and program in the browser.
            'students' => Student::query()
                ->where('status', StudentStatus::Active->value)
                ->with([
                    'enrollments' => fn ($q) => $q->where('academic_year_id', $currentYearId),
                    'activePrograms:programs.id',
                ])
                ->orderBy('last_name')->orderBy('first_name')
                ->get()
                ->map(fn (Student $s) => [
                    'id' => $s->id,
                    'name' => $s->full_name,
                    'level' => $s->enrollments->first()?->level,
                    'level_label' => EducationLevel::tryFrom((string) $s->enrollments->first()?->level)?->label(),
                    'program_ids' => $s->activePrograms->pluck('id')->values(),
                ]),
            'recent' => Distribution::query()
                ->with(['student:id,first_name,last_name', 'itemCategory:id,name', 'recorder:id,name'])
                ->latest('id')
                ->limit(10)
                ->get()
                ->map(fn (Distribution $d) => [
                    'id' => $d->id,
                    'student_id' => $d->student_id,
                    'student' => $d->student?->full_name,
                    'what' => $d->describe($this->settings->get()->currency),
                    'note' => $d->note,
                    'given_on' => $d->given_on?->toDateString(),
                    'recorded_by' => $d->recorder?->name,
                ]),
        ]);
    }

    public function groupHandout(GroupHandoutRequest $request): RedirectResponse
    {
        $count = $this->basicNeeds->groupHandout(
            (float) $request->validated('amount'),
            $request->validated('given_on'),
            $request->validated('note'),
            array_map('intval', $request->validated('student_ids')),
            $request->user(),
        );

        return back()->with('success', "Recorded for {$count} ".str('student')->plural($count).'.');
    }
}
