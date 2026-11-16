<?php

namespace App\Http\Controllers\Support;

use App\Enums\FeeApproval;
use App\Enums\RepairStatus;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\RepairProject;
use App\Services\Settings\SystemSettingsService;
use App\Services\Support\BusinessService;
use App\Services\Support\HomeVisitService;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Organisation-wide lists: families due a visit, repairs, and businesses.
 * Records themselves are added from each student's profile.
 */
#[Middleware('permission:manage home visits', only: ['families'])]
#[Middleware('permission:manage businesses', only: ['businesses'])]
class SupportOverviewController extends Controller
{
    public function __construct(protected SystemSettingsService $settings) {}

    public function families(HomeVisitService $visits): Response
    {
        $repairs = RepairProject::query()->with(['household.students:id,household_id,first_name,last_name'])->latest('id')->get();

        return Inertia::render('Support/Families', [
            'currency' => $this->settings->get()->currency,
            'due' => $visits->dueHouseholds(),
            'repairTotals' => [
                'planned' => $repairs->where('status', RepairStatus::Planned)->count(),
                'in_progress' => $repairs->where('status', RepairStatus::InProgress)->count(),
                'completed' => $repairs->where('status', RepairStatus::Completed)->count(),
                'spent' => round($repairs->where('status', RepairStatus::Completed)->sum(fn ($r) => (float) $r->actual_cost), 2),
            ],
            'repairs' => $repairs->map(fn (RepairProject $r) => [
                'id' => $r->id,
                'household' => $r->household?->head_name ?: 'Unnamed household',
                'student_id' => $r->household?->students->first()?->id,
                'description' => $r->description,
                'estimated_cost' => $r->estimated_cost !== null ? (float) $r->estimated_cost : null,
                'actual_cost' => $r->actual_cost !== null ? (float) $r->actual_cost : null,
                'status' => $r->status->value,
                'status_label' => $r->status->label(),
                'awaiting_approval' => $r->approval_status === FeeApproval::Pending,
                'rejected' => $r->approval_status === FeeApproval::Rejected,
            ]),
        ]);
    }

    public function businesses(BusinessService $businesses): Response
    {
        $due = $businesses->checkinsDue()->pluck('business_id')->all();

        return Inertia::render('Support/Businesses', [
            'currency' => $this->settings->get()->currency,
            'totals' => $businesses->totals(),
            'checkinDays' => $this->settings->businessCheckinDays(),
            'businesses' => Business::query()
                ->with('student:id,first_name,last_name')
                ->withSum('loanRepayments as repaid_total', 'amount')
                ->withMax('checkins as last_checkin', 'checkin_date')
                ->latest('start_date')
                ->get()
                ->map(fn (Business $b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'student_id' => $b->student_id,
                    'student' => $b->student?->full_name,
                    'business_type' => $b->business_type,
                    'start_date' => $b->start_date?->toDateString(),
                    'funding_label' => $b->funding_type->label(),
                    'amount_given' => (float) $b->amount_given,
                    'balance' => $b->loanBalance(),
                    'is_running' => $b->is_running,
                    'last_checkin' => $b->last_checkin,
                    'checkin_due' => in_array($b->id, $due, true),
                ]),
        ]);
    }
}
