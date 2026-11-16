<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\TermRequest;
use App\Models\Term;
use App\Services\Settings\AcademicCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('permission:update settings')]
class TermController extends Controller
{
    public function store(TermRequest $request): RedirectResponse
    {
        Term::create($request->validated());

        return back()->with('success', 'Term added.');
    }

    public function update(TermRequest $request, Term $term): RedirectResponse
    {
        $term->update($request->validated());

        return back()->with('success', 'Term updated.');
    }

    public function makeCurrent(Term $term, AcademicCalendarService $calendar): RedirectResponse
    {
        $calendar->makeTermCurrent($term);

        return back()->with('success', "{$term->name} ({$term->academicYear->name}) is now the current term.");
    }

    public function destroy(Term $term): RedirectResponse
    {
        if ($term->is_current) {
            return back()->with('error', 'The current term cannot be deleted. Make another term current first.');
        }

        if ($term->isInUse()) {
            return back()->with('error', 'This term has results or fees on record, so it cannot be deleted.');
        }

        $term->delete();

        return back()->with('success', 'Term deleted.');
    }
}
