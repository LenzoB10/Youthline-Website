<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\MentorAssignmentRequest;
use App\Http\Requests\Support\MentorshipSessionRequest;
use App\Models\MentorshipSession;
use App\Models\Student;
use App\Services\Support\MentorshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('permission:manage mentorship', except: ['destroySession'])]
#[Middleware('permission:delete support records', only: ['destroySession'])]
class MentorshipController extends Controller
{
    public function __construct(protected MentorshipService $mentorship) {}

    public function assign(MentorAssignmentRequest $request, Student $student): RedirectResponse
    {
        $this->mentorship->assign($student, $request->validated('mentor_id'), $request->validated('start_date'));

        return back()->with('success', $request->validated('mentor_id') ? 'Mentor assigned.' : 'Mentor assignment ended.');
    }

    public function storeSession(MentorshipSessionRequest $request, Student $student): RedirectResponse
    {
        $this->mentorship->recordSession($student, $request->validated(), $request->user());

        return back()->with('success', 'Session recorded.');
    }

    /**
     * Only someone allowed to read a session's notes may change it.
     */
    public function updateSession(MentorshipSessionRequest $request, Student $student, MentorshipSession $mentorshipSession): RedirectResponse
    {
        abort_unless($this->mentorship->canReadNotes($request->user(), $mentorshipSession), 403);

        $mentorshipSession->update($request->validated());

        return back()->with('success', 'Session updated.');
    }

    public function destroySession(Student $student, MentorshipSession $mentorshipSession): RedirectResponse
    {
        $mentorshipSession->delete();

        return back()->with('success', 'Session deleted.');
    }
}
