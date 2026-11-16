<?php

use App\Models\BackupSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$backupSettings = BackupSetting::safeCurrent();

$monthlyBackupPath = config('backup.backup.name').'/monthly';

// Automatic database backups, configured by admins (defaults: enabled, 01:00 daily).
Schedule::command('backup:run --only-db --disable-notifications')
    ->dailyAt($backupSettings->backup_time)
    ->when(fn () => BackupSetting::safeCurrent()->backup_enabled)
    ->withoutOverlapping()
    ->timezone(config('app.timezone'));

// Monthly database backup on the 1st of each month at the configured time.
Schedule::command("backup:run --only-db --disable-notifications --destination-path={$monthlyBackupPath}")
    ->monthlyOn(1, $backupSettings->backup_time)
    ->when(fn () => BackupSetting::safeCurrent()->monthly_backup_enabled)
    ->withoutOverlapping()
    ->timezone(config('app.timezone'));

// Remove backups older than the configured retention period.
Schedule::command('backup:clean --disable-notifications')
    ->dailyAt($backupSettings->backup_time)
    ->when(fn () => BackupSetting::safeCurrent()->backup_enabled || BackupSetting::safeCurrent()->monthly_backup_enabled)
    ->withoutOverlapping()
    ->timezone(config('app.timezone'));

// Reminders engine (Development Plan Phase 5): one in-app digest per person each morning.
Schedule::command('youthline:send-reminders')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->timezone(config('app.timezone'));
