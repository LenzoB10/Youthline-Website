<?php

namespace App\Backup\Strategies;

use App\Models\BackupSetting;
use Carbon\Carbon;
use Spatie\Backup\BackupDestination\BackupCollection;
use Spatie\Backup\Tasks\Cleanup\CleanupStrategy;

class RetentionStrategy extends CleanupStrategy
{
    public function deleteOldBackups(BackupCollection $backups): void
    {
        $settings = BackupSetting::safeCurrent();

        $dailyCutoff = Carbon::now()->subDays((int) $settings->backup_retention_days);
        $monthlyCutoff = Carbon::now()
            ->startOfMonth()
            ->subMonths((int) $settings->monthly_backup_retention_months);

        $backups->each(function ($backup) use ($dailyCutoff, $monthlyCutoff) {
            if ($this->isMonthlyBackup($backup)) {
                if ($backup->date()->lt($monthlyCutoff)) {
                    $backup->disk()->delete($backup->path());
                }

                return;
            }

            if ($backup->date()->lt($dailyCutoff)) {
                $backup->disk()->delete($backup->path());
            }
        });
    }

    protected function isMonthlyBackup($backup): bool
    {
        return str_contains($backup->path(), '/monthly/');
    }
}
