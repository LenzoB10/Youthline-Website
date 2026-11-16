<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\AcademicYearRequest;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\Settings\AcademicCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:update settings')]
class AcademicYearController extends Controller
{
    public function __construct(protected AcademicCalendarService $calendar) {}

    public function index(): Response
    {
        $years = AcademicYear::query()
            ->withCount('enrollments')
            ->with(['terms' => fn ($query) => $query->orderBy('start_date')->orderBy('name')])
            ->orderByDesc('start_date')
            ->orderByDesc('name')
            ->get()
            ->map(fn (AcademicYear $year) => [
                'id' => $year->id,
                'name' => $year->name,
                'start_date' => $year->start_date?->toDateString(),
                'end_date' => $year->end_date?->toDateString(),
                'is_current' => $year->is_current,
                'enrollments_count' => $year->enrollments_count,
                'terms' => $year->terms->map(fn (Term $term) => [
                    'id' => $term->id,
                    'name' => $term->name,
                    'start_date' => $term->start_date?->toDateString(),
                    'end_date' => $term->end_date?->toDateString(),
                    'is_current' => $term->is_current,
                ]),
            ]);

        return Inertia::render('Admin/Settings/AcademicYears/Index', ['years' => $years]);
    }

    public function store(AcademicYearRequest $request): RedirectResponse
    {
        $this->calendar->createYear($request->validated());

        return back()->with('success', 'Academic year added.');
    }

    public function update(AcademicYearRequest $request, AcademicYear $year): RedirectResponse
    {
        $year->update($request->safe()->except('is_current'));

        return back()->with('success', 'Academic year updated.');
    }

    public function makeCurrent(AcademicYear $year): RedirectResponse
    {
        $this->calendar->makeYearCurrent($year);

        return back()->with('success', "{$year->name} is now the current academic year.");
    }

    public function destroy(AcademicYear $year): RedirectResponse
    {
        if ($year->is_current) {
            return back()->with('error', 'The current academic year cannot be deleted. Make another year current first.');
        }

        if ($year->isInUse()) {
            return back()->with('error', 'This year has enrolments, results or fees on record, so it cannot be deleted.');
        }

        $year->delete();

        return back()->with('success', 'Academic year deleted.');
    }
}
