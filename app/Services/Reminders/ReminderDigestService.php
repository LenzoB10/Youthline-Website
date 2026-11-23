<?php

namespace App\Services\Reminders;

use App\Enums\FeeApproval;
use App\Models\Fee;
use App\Models\User;
use App\Notifications\DailyReminderDigest;
use App\Services\Academics\GradeTrendService;
use App\Services\Fees\BalanceService;
use App\Services\Support\BusinessService;
use App\Services\Support\HomeVisitService;
use App\Services\Support\MentorshipService;

/**
 * The reminders engine: once a day, each staff member gets ONE in-app notification
 * listing what is due in the areas they're responsible for.
 */
class ReminderDigestService
{
    /** @var array<string, mixed> */
    protected array $cache = [];

    public function __construct(
        protected BalanceService $balances,
        protected GradeTrendService $trends,
        protected MentorshipService $mentorship,
        protected HomeVisitService $visits,
        protected BusinessService $businesses,
    ) {}

    /**
     * Lines for this user, e.g. "3 home visits are due". Empty when nothing is due.
     *
     * @return array<int, array{text: string, url: string}>
     */
    public function linesFor(User $user): array
    {
        $lines = [];
        $add = function (int $count, string $one, string $many, string $route) use (&$lines) {
            if ($count > 0) {
                $lines[] = ['text' => $count.' '.($count === 1 ? $one : $many), 'url' => route($route)];
            }
        };

        if ($user->can('manage fees')) {
            $add($this->remember('fees', fn () => $this->balances->totals()['students']), 'student has unpaid fees', 'students have unpaid fees', 'finance.unpaid.index');
        }
        if ($user->can('record results')) {
            $add($this->remember('drops', fn () => $this->trends->currentDrops(100)->count()), 'student\'s grades dropped sharply', 'students\' grades dropped sharply', 'dashboard');
        }
        if ($user->can('manage mentorship')) {
            $add($this->remember('mentorship', fn () => $this->mentorship->overdueStudents()->count()), 'student is overdue a mentorship session', 'students are overdue a mentorship session', 'dashboard');
            $add($this->remember('followups', fn () => $this->mentorship->followUpsNeeded()->count()), 'student needs a mentorship follow-up', 'students need a mentorship follow-up', 'dashboard');
        }
        if ($user->can('manage home visits')) {
            $add($this->remember('visits', fn () => $this->visits->dueHouseholds()->count()), 'family is due a home visit', 'families are due a home visit', 'support.families');
        }
        if ($user->can('manage businesses')) {
            $add($this->remember('checkins', fn () => $this->businesses->checkinsDue()->count()), 'business is due a check-in', 'businesses are due a check-in', 'support.businesses');
        }
        if ($user->can('approve payments')) {
            $pending = $this->remember('pendingFinance', fn () => Fee::query()->where('approval_status', FeeApproval::Pending->value)->count());
            $add($pending, 'item is waiting for your approval', 'items are waiting for your approval', 'finance.approvals.index');
        }

        return $lines;
    }

    /**
     * Send today's digest to everyone who has something due. Never twice a day.
     *
     * @return int how many people were notified
     */
    public function sendAll(): int
    {
        $sent = 0;

        User::query()->get()->each(function (User $user) use (&$sent) {
            $alreadyToday = $user->notifications()
                ->where('type', DailyReminderDigest::class)
                ->where('created_at', '>=', now()->startOfDay())
                ->exists();

            $lines = $this->linesFor($user);
            if ($alreadyToday || ! $lines) {
                return;
            }

            $user->notify(new DailyReminderDigest($lines));
            $sent++;
        });

        return $sent;
    }

    protected function remember(string $key, callable $compute): int
    {
        return $this->cache[$key] ??= (int) $compute();
    }
}
