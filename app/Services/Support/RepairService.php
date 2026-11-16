<?php

namespace App\Services\Support;

use App\Enums\FeeApproval;
use App\Enums\RepairStatus;
use App\Models\Household;
use App\Models\RepairProject;
use App\Models\User;
use App\Notifications\ApprovalDecided;
use App\Notifications\RepairAwaitingApproval;
use App\Services\Notifications\AlertRecipients;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Home repairs: Planned → In progress → Completed. Budgets above the Admin's
 * limit wait for approval before work can start.
 */
class RepairService
{
    public function __construct(
        protected SystemSettingsService $settings,
        protected PhotoService $photos,
        protected AlertRecipients $recipients,
    ) {}

    /**
     * @param  array{description: string, estimated_cost: numeric-string|float}  $data
     * @param  array<int, UploadedFile>  $beforePhotos
     */
    public function create(Household $household, array $data, array $beforePhotos, User $by): RepairProject
    {
        $waits = $this->settings->repairNeedsApproval($data['estimated_cost']) && ! $by->can('approve repairs');

        $repair = DB::transaction(function () use ($household, $data, $beforePhotos, $by, $waits) {
            $repair = RepairProject::create([
                'household_id' => $household->id,
                'description' => $data['description'],
                'estimated_cost' => $data['estimated_cost'],
                'status' => RepairStatus::Planned,
                'approval_status' => $waits ? FeeApproval::Pending : FeeApproval::Approved,
                'approved_by' => $waits ? null : $by->id,
                'approved_at' => $waits ? null : now(),
                'created_by' => $by->id,
            ]);
            $this->photos->storeMany($repair, $beforePhotos, 'before', $by);

            return $repair;
        });

        if ($waits) {
            Notification::send(
                $this->recipients->withPermission('approve repairs', except: $by),
                new RepairAwaitingApproval($repair->load('household', 'creator')),
            );
        }

        return $repair;
    }

    /**
     * Move the repair one step forward. Completing it needs the actual cost.
     *
     * @param  array{actual_cost?: ?float, date?: ?string}  $data
     * @param  array<int, UploadedFile>  $afterPhotos
     */
    public function advance(RepairProject $repair, array $data, array $afterPhotos, User $by): RepairProject
    {
        if (! $repair->isApproved()) {
            throw ValidationException::withMessages(['status' => 'This repair budget hasn\'t been approved yet.']);
        }

        $next = $repair->status->next();
        if (! $next) {
            throw ValidationException::withMessages(['status' => 'This repair is already completed.']);
        }

        if ($next === RepairStatus::Completed && blank($data['actual_cost'] ?? null)) {
            throw ValidationException::withMessages(['actual_cost' => 'Enter what the repair actually cost.']);
        }

        DB::transaction(function () use ($repair, $next, $data, $afterPhotos, $by) {
            $date = $data['date'] ?? now()->toDateString();

            $repair->update($next === RepairStatus::InProgress
                ? ['status' => $next, 'started_on' => $date]
                : ['status' => $next, 'completed_on' => $date, 'actual_cost' => $data['actual_cost']]);

            $this->photos->storeMany($repair, $afterPhotos, $next === RepairStatus::Completed ? 'after' : 'before', $by);
        });

        return $repair;
    }

    public function approve(RepairProject $repair, User $admin): void
    {
        $this->decide($repair, $admin, true);
    }

    public function reject(RepairProject $repair, User $admin, string $reason): void
    {
        $this->decide($repair, $admin, false, $reason);
    }

    public function delete(RepairProject $repair): void
    {
        DB::transaction(function () use ($repair) {
            $this->photos->deleteAllFor($repair);
            $repair->delete();
        });
    }

    protected function decide(RepairProject $repair, User $admin, bool $approved, ?string $reason = null): void
    {
        if ($repair->approval_status !== FeeApproval::Pending) {
            throw ValidationException::withMessages(['repair' => 'This repair budget has already been decided.']);
        }

        $repair->update([
            'approval_status' => $approved ? FeeApproval::Approved : FeeApproval::Rejected,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'rejection_reason' => $approved ? null : $reason,
        ]);

        $repair->loadMissing('creator', 'household.students');
        $studentId = $repair->household->students->first()?->id;
        if ($repair->creator && ! $repair->creator->is($admin) && $studentId) {
            $currency = $this->settings->get()->currency;
            $repair->creator->notify(new ApprovalDecided(
                "The repair budget of {$currency} ".number_format((float) $repair->estimated_cost, 2)." for the {$repair->household->head_name} household",
                $approved,
                $admin->name,
                $reason,
                $studentId,
                '#home',
            ));
        }
    }
}
