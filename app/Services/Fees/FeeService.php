<?php

namespace App\Services\Fees;

use App\Enums\FeeApproval;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Notifications\ApprovalDecided;
use App\Notifications\ScholarshipAwaitingApproval;
use App\Services\Notifications\AlertRecipients;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class FeeService
{
    public function __construct(
        protected SystemSettingsService $settings,
        protected AlertRecipients $recipients,
    ) {}

    /**
     * Record what a student owes for a term. A student's FIRST fee is a new
     * scholarship: when Settings require it, a manager's entry waits for the Admin.
     *
     * @param  array<string, mixed>  $data  validated FeeRequest data
     */
    public function create(Student $student, array $data, User $by): Fee
    {
        $needsApproval = $this->settings->scholarshipNeedsApproval()
            && ! $by->can('approve payments')
            && $this->isNewScholarship($student);

        $fee = Fee::create([
            ...$this->attributes($student, $data),
            'student_id' => $student->id,
            'approval_status' => $needsApproval ? FeeApproval::Pending : FeeApproval::Approved,
            'approved_by' => $needsApproval ? null : $by->id,
            'approved_at' => $needsApproval ? null : now(),
            'created_by' => $by->id,
        ]);

        if ($needsApproval) {
            Notification::send(
                $this->recipients->withPermission('approve payments', except: $by),
                new ScholarshipAwaitingApproval($fee->load('student', 'term.academicYear', 'creator')),
            );
        }

        return $fee;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Fee $fee, array $data): Fee
    {
        $fee->update($this->attributes($fee->student, $data));

        return $fee;
    }

    /**
     * A fee with any payment on it is part of the ledger and can't be removed.
     */
    public function delete(Fee $fee): void
    {
        if ($fee->payments()->exists()) {
            throw ValidationException::withMessages([
                'fee' => 'This term has payments recorded against it, so it can\'t be deleted. Mark a wrong payment as an error instead.',
            ]);
        }

        $fee->delete();
    }

    public function approve(Fee $fee, User $admin): void
    {
        $this->ensurePending($fee);

        $fee->update([
            'approval_status' => FeeApproval::Approved,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->tellCreator($fee, true, $admin);
    }

    public function reject(Fee $fee, User $admin, string $reason): void
    {
        $this->ensurePending($fee);

        $fee->update([
            'approval_status' => FeeApproval::Rejected,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->tellCreator($fee, false, $admin, $reason);
    }

    public function isNewScholarship(Student $student): bool
    {
        return ! $student->fees()->approved()->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function attributes(Student $student, array $data): array
    {
        $term = Term::query()->findOrFail($data['term_id']);

        return [
            'term_id' => $term->id,
            // Default to the school the student attends that year.
            'institution_id' => $data['institution_id'] ?? Enrollment::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $term->academic_year_id)
                ->value('institution_id'),
            'amount_owed' => $data['amount_owed'],
            'youthline_share' => $data['youthline_share'],
            'notes' => $data['notes'] ?? null,
        ];
    }

    protected function ensurePending(Fee $fee): void
    {
        if ($fee->approval_status !== FeeApproval::Pending) {
            throw ValidationException::withMessages(['fee' => 'This scholarship has already been decided.']);
        }
    }

    protected function tellCreator(Fee $fee, bool $approved, User $admin, ?string $reason = null): void
    {
        $fee->loadMissing('creator', 'student', 'term.academicYear');

        if (! $fee->creator || $fee->creator->is($admin)) {
            return;
        }

        $term = trim(($fee->term?->academicYear?->name ?? '').' '.($fee->term?->name ?? ''));
        $fee->creator->notify(new ApprovalDecided(
            "The new scholarship for {$fee->student->full_name} ({$term})",
            $approved,
            $admin->name,
            $reason,
            route('students.show', $fee->student_id).'#academics',
        ));
    }
}
