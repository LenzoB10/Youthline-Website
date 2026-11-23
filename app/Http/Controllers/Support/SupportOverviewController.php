<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\Settings\SystemSettingsService;
use App\Services\Support\BusinessService;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Support → Businesses: every youth business, with totals and check-ins due.
 * (Families live in HouseholdController.)
 */
#[Middleware('permission:manage businesses', only: ['businesses'])]
class SupportOverviewController extends Controller
{
    public function __construct(protected SystemSettingsService $settings) {}

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
