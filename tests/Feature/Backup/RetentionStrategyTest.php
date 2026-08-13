<?php

use App\Backup\Strategies\RetentionStrategy;
use App\Models\BackupSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Config\Config;

uses(RefreshDatabase::class);

it('deletes backups older than the configured retention period', function () {
    Storage::fake('local');

    $oldName = Carbon::now()->subDays(40)->format('Y-m-d-H-i-s').'.zip';
    $recentName = Carbon::now()->subDays(10)->format('Y-m-d-H-i-s').'.zip';

    Storage::disk('local')->put('Laravel/'.$oldName, 'old');
    Storage::disk('local')->put('Laravel/'.$recentName, 'recent');

    BackupSetting::current()->update(['backup_retention_days' => 30]);

    $destination = BackupDestination::create('local', config('backup.backup.name'));
    $strategy = new RetentionStrategy(Config::fromArray(config('backup')));

    $strategy->deleteOldBackups($destination->backups());

    Storage::disk('local')->assertMissing('Laravel/'.$oldName);
    Storage::disk('local')->assertExists('Laravel/'.$recentName);
});

it('keeps all backups when none are older than the retention period', function () {
    Storage::fake('local');

    $recentName = Carbon::now()->subDays(2)->format('Y-m-d-H-i-s').'.zip';

    Storage::disk('local')->put('Laravel/'.$recentName, 'recent');

    BackupSetting::current()->update(['backup_retention_days' => 30]);

    $destination = BackupDestination::create('local', config('backup.backup.name'));
    $strategy = new RetentionStrategy(Config::fromArray(config('backup')));

    $strategy->deleteOldBackups($destination->backups());

    Storage::disk('local')->assertExists('Laravel/'.$recentName);
});

it('deletes monthly backups older than the configured retention months', function () {
    Storage::fake('local');

    $oldName = Carbon::now()->startOfMonth()->subMonths(13)->format('Y-m-d-H-i-s').'.zip';
    $recentName = Carbon::now()->startOfMonth()->subMonths(2)->format('Y-m-d-H-i-s').'.zip';

    Storage::disk('local')->put('Laravel/monthly/'.$oldName, 'old monthly');
    Storage::disk('local')->put('Laravel/monthly/'.$recentName, 'recent monthly');

    BackupSetting::current()->update(['monthly_backup_retention_months' => 12]);

    $destination = BackupDestination::create('local', config('backup.backup.name'));
    $strategy = new RetentionStrategy(Config::fromArray(config('backup')));

    $strategy->deleteOldBackups($destination->backups());

    Storage::disk('local')->assertMissing('Laravel/monthly/'.$oldName);
    Storage::disk('local')->assertExists('Laravel/monthly/'.$recentName);
});

it('applies day retention to daily backups and month retention to monthly backups', function () {
    Storage::fake('local');

    $oldDailyName = Carbon::now()->subDays(40)->format('Y-m-d-H-i-s').'.zip';
    $oldMonthlyName = Carbon::now()->startOfMonth()->subMonths(13)->format('Y-m-d-H-i-s').'.zip';
    $recentMonthlyName = Carbon::now()->startOfMonth()->subMonths(2)->format('Y-m-d-H-i-s').'.zip';

    Storage::disk('local')->put('Laravel/'.$oldDailyName, 'old daily');
    Storage::disk('local')->put('Laravel/monthly/'.$oldMonthlyName, 'old monthly');
    Storage::disk('local')->put('Laravel/monthly/'.$recentMonthlyName, 'recent monthly');

    BackupSetting::current()->update([
        'backup_retention_days' => 30,
        'monthly_backup_retention_months' => 12,
    ]);

    $destination = BackupDestination::create('local', config('backup.backup.name'));
    $strategy = new RetentionStrategy(Config::fromArray(config('backup')));

    $strategy->deleteOldBackups($destination->backups());

    Storage::disk('local')->assertMissing('Laravel/'.$oldDailyName);
    Storage::disk('local')->assertMissing('Laravel/monthly/'.$oldMonthlyName);
    Storage::disk('local')->assertExists('Laravel/monthly/'.$recentMonthlyName);
});