<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\MentorRequest;
use App\Models\Mentor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Mentors and counsellors. They don't log in — this is only a list.
 */
#[Middleware('permission:manage mentorship', except: ['destroy'])]
#[Middleware('permission:delete support records', only: ['destroy'])]
class MentorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Support/Mentors', [
            'mentors' => Mentor::query()
                ->withCount(['currentAssignments as students_count', 'sessions'])
                ->withMax('sessions as last_session', 'session_date')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get()
                ->map(fn (Mentor $m) => [
                    ...$m->only(['id', 'name', 'phone', 'email', 'is_active', 'students_count', 'sessions_count']),
                    'last_session' => $m->last_session,
                    'in_use' => $m->sessions_count > 0 || $m->assignments()->exists(),
                ]),
        ]);
    }

    public function store(MentorRequest $request): RedirectResponse
    {
        Mentor::create([...$request->validated(), 'is_active' => $request->boolean('is_active', true)]);

        return back()->with('success', 'Mentor added.');
    }

    public function update(MentorRequest $request, Mentor $mentor): RedirectResponse
    {
        $mentor->update([...$request->validated(), 'is_active' => $request->boolean('is_active', $mentor->is_active)]);

        return back()->with('success', 'Mentor updated.');
    }

    /**
     * A mentor with history is deactivated instead, so past sessions keep their mentor.
     */
    public function destroy(Mentor $mentor): RedirectResponse
    {
        if ($mentor->isInUse()) {
            return back()->with('error', 'This mentor has sessions or students on record. Mark them inactive instead.');
        }

        $mentor->delete();

        return back()->with('success', 'Mentor deleted.');
    }
}
