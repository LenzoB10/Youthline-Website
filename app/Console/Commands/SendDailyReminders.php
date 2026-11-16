<?php

namespace App\Console\Commands;

use App\Services\Reminders\ReminderDigestService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('youthline:send-reminders')]
#[Description('Send each staff member one in-app summary of what is due today (visits, sessions, check-ins, fees, approvals).')]
class SendDailyReminders extends Command
{
    public function handle(ReminderDigestService $digests): int
    {
        $sent = $digests->sendAll();
        $this->info("Reminders sent to {$sent} ".str('person')->plural($sent).'.');

        return self::SUCCESS;
    }
}
