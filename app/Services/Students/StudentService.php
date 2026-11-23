<?php

namespace App\Services\Students;

use App\Enums\StudentStatus;
use App\Models\Enrollment;
use App\Models\Household;
use App\Models\Student;
use App\Models\StudentProgram;
use App\Models\User;
use App\Services\Settings\AcademicCalendarService;
use App\Services\Settings\ReferenceLookupService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registering and maintaining a student's core record (System Guide 2.1).
 * History is kept: programs are ended (not deleted) and enrolments are per year.
 */
class StudentService
{
    /** Fields copied straight from the form onto the student row. */
    protected const DETAIL_FIELDS = [
        'first_name', 'last_name', 'date_of_birth', 'gender', 'phone', 'home_location',
        'guardian_name', 'guardian_phone', 'guardian_relationship',
    ];

    public function __construct(
        protected AcademicCalendarService $calendar,
        protected StudentDocumentService $documents,
        protected ReferenceLookupService $lookup,
    ) {}

    /**
     * Add Student: starts as Applicant, with this year's school/level and programs.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data, User $by): Student
    {
        return DB::transaction(function () use ($data, $by) {
            $student = Student::create([
                ...$this->details($data),
                'household_id' => $this->resolveHousehold($data),
                'status' => StudentStatus::Applicant,
                'created_by' => $by->getKey(),
            ]);

            $institution = $this->lookup->institution($data['institution_name'] ?? null, $data['level'] ?? null);

            if ($institution || filled($data['level'] ?? null)) {
                Enrollment::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $this->calendar->current()?->id,
                    'institution_id' => $institution?->id,
                    'level' => $data['level'] ?? null,
                    'course' => $data['course'] ?? null,
                ]);
            }

            $this->syncPrograms($student, $data['program_ids'] ?? []);

            return $student;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Student $student, array $data): Student
    {
        return DB::transaction(function () use ($student, $data) {
            $student->update([
                ...$this->details($data),
                'household_id' => $this->resolveHousehold($data),
            ]);

            $this->syncPrograms($student, $data['program_ids'] ?? []);

            return $student;
        });
    }

    /**
     * Join new programs and end the ones no longer selected. Ended memberships keep
     * their row (with an end date) so program history and counts stay accurate.
     *
     * @param  array<int, int|string>  $programIds
     */
    public function syncPrograms(Student $student, array $programIds, ?Carbon $on = null): void
    {
        $on ??= today();
        $wanted = collect($programIds)->map(fn ($id) => (int) $id)->unique();
        $current = $student->studentPrograms()->whereNull('end_date')->get();

        $current->reject(fn (StudentProgram $row) => $wanted->contains($row->program_id))
            ->each(fn (StudentProgram $row) => $row->update(['end_date' => $on]));

        $wanted->diff($current->pluck('program_id'))
            ->each(fn (int $programId) => StudentProgram::create([
                'student_id' => $student->id,
                'program_id' => $programId,
                'start_date' => $on,
            ]));
    }

    /**
     * Change status. Leaving always records a reason and date; returning to Active
     * clears them (the previous values stay in the audit log).
     *
     * Undoing an exit (e.g. Graduated set by mistake) reopens the programs that
     * the exit closed, so the correction leaves the student as they were.
     */
    public function changeStatus(Student $student, StudentStatus $to, ?string $reason = null, ?Carbon $on = null): Student
    {
        return DB::transaction(function () use ($student, $to, $reason, $on) {
            $on ??= today();

            if ($student->status->endsPrograms() && ! $to->endsPrograms() && $student->exit_date) {
                $student->studentPrograms()
                    ->whereDate('end_date', $student->exit_date)
                    ->whereNotIn('program_id', $student->studentPrograms()->whereNull('end_date')->select('program_id'))
                    ->update(['end_date' => null]);
            }

            if ($to->endsPrograms()) {
                $this->syncPrograms($student, [], $on);
            }

            $student->update([
                'status' => $to,
                'exit_reason' => $to->requiresReason() ? $reason : null,
                'exit_date' => $to->requiresReason() ? $on : null,
            ]);

            return $student;
        });
    }

    /**
     * Permanent deletion — Admin only. Removes the stored files too, since the
     * database cascade would otherwise leave private documents orphaned on disk.
     */
    public function delete(Student $student): void
    {
        DB::transaction(function () use ($student) {
            $student->documents->each(fn ($document) => $this->documents->delete($document));
            $student->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function details(array $data): array
    {
        return array_intersect_key($data, array_flip(self::DETAIL_FIELDS));
    }

    /**
     * Link to an existing household (siblings share one), create a new one, or none.
     *
     * @param  array<string, mixed>  $data
     */
    protected function resolveHousehold(array $data): ?int
    {
        return match ($data['household_mode'] ?? 'none') {
            'existing' => (int) $data['household_id'],
            'new' => Household::create($data['household'])->id,
            default => null,
        };
    }
}
