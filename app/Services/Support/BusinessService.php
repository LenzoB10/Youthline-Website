<?php

namespace App\Services\Support;

use App\Enums\FundingType;
use App\Models\Business;
use App\Models\BusinessCheckin;
use App\Models\LoanRepayment;
use App\Models\Student;
use App\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusinessService
{
    public function __construct(protected SystemSettingsService $settings) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Student $student, array $data): Business
    {
        return Business::create([...$data, 'student_id' => $student->id, 'is_running' => true]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Business $business, array $data): Business
    {
        // A loan can't be turned into a grant (or shrunk) below what has been repaid.
        $repaid = $business->repaidTotal();
        if ($repaid > 0 && (($data['funding_type'] ?? null) === FundingType::Grant->value || (float) $data['amount_given'] < $repaid)) {
            throw ValidationException::withMessages([
                'amount_given' => 'Repayments of '.number_format($repaid, 2).' are recorded, so this must stay a loan of at least that amount.',
            ]);
        }

        $business->update($data);

        return $business;
    }

    /**
     * A check-in also updates whether the business is still running.
     *
     * @param  array<string, mixed>  $data
     */
    public function checkIn(Business $business, array $data, User $by): BusinessCheckin
    {
        return DB::transaction(function () use ($business, $data, $by) {
            $checkin = $business->checkins()->create([...$data, 'recorded_by' => $by->id]);
            $business->update(['is_running' => (bool) $data['is_running']]);

            return $checkin;
        });
    }

    /**
     * @param  array{amount: float|string, paid_on: string}  $data
     */
    public function recordRepayment(Business $business, array $data, User $by): LoanRepayment
    {
        if ($business->funding_type !== FundingType::Loan) {
            throw ValidationException::withMessages(['amount' => 'This business was given a grant, so there is nothing to repay.']);
        }
        if ((float) $data['amount'] > $business->loanBalance()) {
            throw ValidationException::withMessages(['amount' => 'Only '.number_format($business->loanBalance(), 2).' is left to repay.']);
        }

        return $business->loanRepayments()->create([...$data, 'recorded_by' => $by->id]);
    }

    /**
     * Running businesses with no check-in within the set period.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function checkinsDue(): Collection
    {
        $days = $this->settings->businessCheckinDays();

        return Business::query()
            ->where('is_running', true)
            ->with('student')
            ->withMax('checkins as last_checkin_date', 'checkin_date')
            ->get()
            ->filter(function (Business $b) use ($days) {
                $from = $b->last_checkin_date ? Carbon::parse($b->last_checkin_date) : $b->start_date;

                return ! $from || $from->lt(now()->subDays($days)->startOfDay());
            })
            ->map(fn (Business $b) => [
                'business_id' => $b->id,
                'business' => $b->name,
                'student_id' => $b->student_id,
                'student' => $b->student?->full_name,
                'last_checkin' => $b->last_checkin_date ? Carbon::parse($b->last_checkin_date)->toDateString() : null,
            ])
            ->values();
    }

    /**
     * Admin overview: started, still running, money given and repaid.
     *
     * @return array{started: int, running: int, given: float, repaid: float, outstanding: float}
     */
    public function totals(): array
    {
        $businesses = Business::query()->withSum('loanRepayments as repaid_total', 'amount')->get();

        return [
            'started' => $businesses->count(),
            'running' => $businesses->where('is_running', true)->count(),
            'given' => round($businesses->sum(fn (Business $b) => (float) $b->amount_given), 2),
            'repaid' => round($businesses->sum(fn (Business $b) => $b->repaidTotal()), 2),
            'outstanding' => round($businesses->sum(fn (Business $b) => $b->loanBalance()), 2),
        ];
    }
}
