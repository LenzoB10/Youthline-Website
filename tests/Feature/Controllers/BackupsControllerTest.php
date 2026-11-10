<?php

use App\Models\BackupSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

describe('BackupsController', function () {
    beforeEach(function () {
        Storage::fake('local');

        $this->listPermission = Permission::create(['name' => 'list backups']);
        $this->createPermission = Permission::create(['name' => 'create backup']);
        $this->downloadPermission = Permission::create(['name' => 'download backup']);
        $this->deletePermission = Permission::create(['name' => 'delete backup']);
        $this->updateSettingsPermission = Permission::create(['name' => 'update backup settings']);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            $this->listPermission,
            $this->createPermission,
            $this->downloadPermission,
            $this->deletePermission,
            $this->updateSettingsPermission,
        ]);

        $this->actingAs($this->user);
    });

    describe('index method', function () {
        it('can display backups index page', function () {
            $response = $this->get('/backups');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page->component('Backups/Index')
                ->has('backups')
            );
        });

        it('includes backup settings with defaults', function () {
            $response = $this->get('/backups');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page->component('Backups/Index')
                ->has('settings')
                ->where('settings.backup_enabled', true)
                ->where('settings.backup_retention_days', 30)
                ->where('settings.backup_time', '01:00')
                ->where('settings.monthly_backup_enabled', true)
                ->where('settings.monthly_backup_retention_months', 12)
            );
        });

        it('lists existing backup files', function () {
            Storage::disk('local')->put(config('backup.backup.name').'/2026-08-13-10-00-00.zip', 'fake zip content');

            $response = $this->get('/backups');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page->component('Backups/Index')
                ->has('backups.data', 1)
                ->where('backups.data.0.filename', '2026-08-13-10-00-00.zip')
                ->where('backups.data.0.type', 'daily')
                ->where('backups.data.0.created_at', '2026-08-13 10:00:00')
                ->where('backups.total', 1)
            );
        });

        it('tags monthly backups with the monthly type', function () {
            Storage::disk('local')->put(config('backup.backup.name').'/2026-08-02-10-00-00.zip', 'daily');
            Storage::disk('local')->put(config('backup.backup.name').'/monthly/2026-08-01-10-00-00.zip', 'monthly');

            $response = $this->get('/backups');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page->component('Backups/Index')
                ->where('backups.data.0.type', 'daily')
                ->where('backups.data.1.type', 'monthly')
            );
        });

        it('denies access to unauthorized user', function () {
            $unauthorizedUser = User::factory()->create();
            $this->actingAs($unauthorizedUser);

            $response = $this->get('/backups');

            $response->assertForbidden();
        });
    });

    describe('store method', function () {
        it('can create a database backup', function () {
            Artisan::shouldReceive('call')
                ->once()
                ->with('backup:run', [
                    '--only-db' => true,
                    '--disable-notifications' => true,
                    '--no-interaction' => true,
                ])
                ->andReturn(0);

            $response = $this->post('/backups');

            $response->assertRedirect(route('admin.backups.index'));
            $response->assertSessionHas('success', 'Database backup created successfully.');

            $this->assertDatabaseHas('activity_log', [
                'event' => 'created',
                'description' => 'Database backup created',
            ]);
        });

        it('flashes an error when the backup command fails', function () {
            Artisan::shouldReceive('call')
                ->once()
                ->andReturn(1);

            $response = $this->post('/backups');

            $response->assertRedirect(route('admin.backups.index'));
            $response->assertSessionHas('error');

            $this->assertDatabaseHas('activity_log', [
                'event' => 'failed',
                'description' => 'Database backup failed',
            ]);
        });

        it('denies access to unauthorized user', function () {
            $unauthorizedUser = User::factory()->create();
            $this->actingAs($unauthorizedUser);

            $response = $this->post('/backups');

            $response->assertForbidden();
        });
    });

    describe('download method', function () {
        beforeEach(function () {
            Storage::disk('local')->put(config('backup.backup.name').'/2026-08-13-10-00-00.zip', 'fake zip content');
        });

        it('can download an existing backup', function () {
            $response = $this->get('/backups/2026-08-13-10-00-00.zip/download');

            $response->assertOk();
        });

        it('redirects when backup does not exist', function () {
            $response = $this->get('/backups/does-not-exist.zip/download');

            $response->assertRedirect(route('admin.backups.index'));
            $response->assertSessionHas('error', 'Backup file not found.');
        });

        it('denies access to unauthorized user', function () {
            $unauthorizedUser = User::factory()->create();
            $this->actingAs($unauthorizedUser);

            $response = $this->get('/backups/2026-08-13-10-00-00.zip/download');

            $response->assertForbidden();
        });
    });

    describe('destroy method', function () {
        beforeEach(function () {
            Storage::disk('local')->put(config('backup.backup.name').'/2026-08-13-10-00-00.zip', 'fake zip content');
        });

        it('can delete an existing backup', function () {
            $response = $this->delete('/backups/2026-08-13-10-00-00.zip');

            $response->assertRedirect(route('admin.backups.index'));
            $response->assertSessionHas('success', 'Backup deleted successfully.');

            Storage::disk('local')->assertMissing(config('backup.backup.name').'/2026-08-13-10-00-00.zip');
        });

        it('redirects when backup does not exist', function () {
            $response = $this->delete('/backups/does-not-exist.zip');

            $response->assertRedirect(route('admin.backups.index'));
            $response->assertSessionHas('error', 'Backup file not found.');
        });

        it('denies access to unauthorized user', function () {
            $unauthorizedUser = User::factory()->create();
            $this->actingAs($unauthorizedUser);

            $response = $this->delete('/backups/2026-08-13-10-00-00.zip');

            $response->assertForbidden();
        });
    });

    describe('updateSettings method', function () {
        it('can update backup settings', function () {
            $response = $this->put('/backups/settings', [
                'backup_enabled' => false,
                'backup_time' => '02:30',
                'backup_retention_days' => 45,
                'monthly_backup_enabled' => false,
                'monthly_backup_retention_months' => 24,
            ]);

            $response->assertRedirect(route('admin.backups.index'));
            $response->assertSessionHas('success', 'Backup settings updated successfully.');

            $settings = BackupSetting::current();
            expect($settings->backup_enabled)->toBeFalse();
            expect($settings->backup_retention_days)->toBe(45);
            expect($settings->backup_time)->toBe('02:30');
            expect($settings->monthly_backup_enabled)->toBeFalse();
            expect($settings->monthly_backup_retention_months)->toBe(24);

            $this->assertDatabaseHas('activity_log', [
                'event' => 'updated',
                'description' => 'Database backup settings updated',
            ]);
        });

        it('creates default settings when none exist yet', function () {
            $response = $this->put('/backups/settings', [
                'backup_enabled' => true,
                'backup_time' => '01:00',
                'backup_retention_days' => 30,
                'monthly_backup_enabled' => true,
                'monthly_backup_retention_months' => 12,
            ]);

            $response->assertRedirect(route('admin.backups.index'));

            $this->assertDatabaseHas('backup_settings', [
                'id' => 1,
                'backup_enabled' => 1,
                'backup_retention_days' => 30,
                'monthly_backup_enabled' => 1,
                'monthly_backup_retention_months' => 12,
            ]);
        });

        it('validates backup time format', function () {
            $response = $this->put('/backups/settings', [
                'backup_time' => 'not-a-time',
                'backup_retention_days' => 30,
                'monthly_backup_retention_months' => 12,
            ]);

            $response->assertSessionHasErrors('backup_time');
        });

        it('validates retention days range', function () {
            $response = $this->put('/backups/settings', [
                'backup_time' => '01:00',
                'backup_retention_days' => 500,
                'monthly_backup_retention_months' => 12,
            ]);

            $response->assertSessionHasErrors('backup_retention_days');
        });

        it('validates monthly retention months range', function () {
            $response = $this->put('/backups/settings', [
                'backup_time' => '01:00',
                'backup_retention_days' => 30,
                'monthly_backup_retention_months' => 0,
            ]);

            $response->assertSessionHasErrors('monthly_backup_retention_months');
        });

        it('denies access to unauthorized user', function () {
            $unauthorizedUser = User::factory()->create();
            $this->actingAs($unauthorizedUser);

            $response = $this->put('/backups/settings', [
                'backup_enabled' => true,
                'backup_time' => '01:00',
                'backup_retention_days' => 30,
                'monthly_backup_enabled' => true,
                'monthly_backup_retention_months' => 12,
            ]);

            $response->assertForbidden();
        });
    });
});
