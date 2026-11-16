<?php

namespace App\Services\Support;

use App\Enums\StudentStatus;
use App\Models\MentorAssignment;
use App\Models\MentorshipSession;
use App\Models\Student;
use App\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MentorshipService
{
    public function __construct(protected SystemSettingsService $settings) {}

    /**
     * Give the student a mentor (ending the previous assignment), or end it ($mentorId null).
     */
    public function assign(Student $student, ?int $mentorId, ?string $startDate = null): void
    {
        DB::transaction(function () use ($student, $mentorId, $startDate) {
            $current = $student->currentMentorAssignment;
            if ($current && $current->mentor_id === $mentorId) {
                return;
            }

            $current?->update(['end_date' => now()->toDateString()]);

            if ($mentorId) {
                MentorAssignment::create([
                    'student_id' => $student->id,
                    'mentor_id' => $mentorId,
                    'start_date' => $startDate ?? now()->toDateString(),
                ]);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordSession(Student $student, array $data, User $by): MentorshipSession
    {
        return MentorshipSession::create([
            ...$data,
            'mentor_id' => $data['mentor_id'] ?? $student->currentMentorAssignment?->mentor_id,
            'student_id' => $student->id,
            'recorded_by' => $by->id,
        ]);
    }

    /**
     * Notes are restricted: the Admin and the author always; other managers only
     * when Settings → Approvals & Policies shares them with all managers.
     */
    public function canReadNotes(User $user, MentorshipSession $session): bool
    {
        if ($user->can('view all session notes') || $session->recorded_by === $user->id) {
            return true;
        }

        return $this->settings->sessionNotesVisibleToAllManagers() && $user->can('manage mentorship');
    }

    /**
     * @return array{last_session: ?string, follow_up_needed: bool, is_overdue: bool, overdue_days: int}
     */
    public function summary(Student $student): array
    {
        $latest = $student->mentorshipSessions()->latest('session_date')->latest('id')->first();
        $assignment = $student->currentMentorAssignment;

        return [
            'last_session' => $latest?->session_date?->toDateString(),
            'follow_up_needed' => (bool) $latest?->follow_up_needed,
            'is_overdue' => $assignment && $this->isOverdue($latest?->session_date, $assignment->start_date),
            'overdue_days' => $this->settings->mentorshipOverdueDays(),
        ];
    }

    /**
     * Active students with a mentor but no session within the overdue period.
     *
     * @return Collection<int, array{student_id: int, student: string, mentor: ?string, last_session: ?string}>
     */
    public function overdueStudents(): Collection
    {
        return Student::query()
            ->where('status', StudentStatus::Active->value)
            ->whereHas('currentMentorAssignment')
            ->with(['currentMentorAssignment.mentor'])
            ->withMax('mentorshipSessions as last_session_date', 'session_date')
            ->get()
            ->filter(fn (Student $s) => $this->isOverdue(
                $s->last_session_date ? Carbon::parse($s->last_session_date) : null,
                $s->currentMentorAssignment->start_date,
            ))
            ->sortBy(fn (Student $s) => $s->last_session_date ?? '0000')
            ->map(fn (Student $s) => [
                'student_id' => $s->id,
                'student' => $s->full_name,
                'mentor' => $s->currentMentorAssignment->mentor?->name,
                'last_session' => $s->last_session_date ? Carbon::parse($s->last_session_date)->toDateString() : null,
            ])
            ->values();
    }

    /**
     * Students whose most recent session asked for a follow-up.
     *
     * @return Collection<int, array{student_id: int, student: string, session_date: ?string}>
     */
    public function followUpsNeeded(): Collection
    {
        return MentorshipSession::query()
            ->with('student')
            ->orderByDesc('session_date')
            ->orderByDesc('id')
            ->get()
            ->unique('student_id')
            ->filter(fn (MentorshipSession $s) => $s->follow_up_needed && $s->student?->status === StudentStatus::Active)
            ->map(fn (MentorshipSession $s) => [
                'student_id' => $s->student_id,
                'student' => $s->student->full_name,
                'session_date' => $s->session_date?->toDateString(),
            ])
            ->values();
    }

    protected function isOverdue(?Carbon $lastSession, ?Carbon $assignedOn): bool
    {
        $from = $lastSession ?? $assignedOn;

        return $from === null || $from->lt(now()->subDays($this->settings->mentorshipOverdueDays())->startOfDay());
    }
}
