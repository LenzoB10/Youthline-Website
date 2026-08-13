<?php

namespace App\Http\Controllers;

use App\Models\BackupSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Backup\BackupDestination\BackupDestination;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Throwable;

#[Middleware('permission:list backups', only: ['index'])]
#[Middleware('permission:create backup', only: ['store'])]
#[Middleware('permission:download backup', only: ['download'])]
#[Middleware('permission:delete backup', only: ['destroy'])]
#[Middleware('permission:update backup settings', only: ['updateSettings'])]
class BackupsController extends Controller
{
    public function index(Request $request): Response
    {
        $search = strtolower((string) $request->input('search', ''));
        $sortBy = (string) $request->input('sort_by', 'created_at');
        $sortDirection = (string) $request->input('sort_direction', 'desc');
        $perPage = $request->integer('per_page', 10);
        $page = $request->integer('page', 1);

        $backups = $this->destination()
            ->backups()
            ->map(fn ($backup) => [
                'id' => basename($backup->path()),
                'filename' => basename($backup->path()),
                'type' => str_contains($backup->path(), '/monthly/') ? 'monthly' : 'daily',
                'size' => $backup->sizeInBytes(),
                'created_at' => $backup->date()->format('Y-m-d H:i:s'),
            ])
            ->filter(fn ($backup) => $search === '' || str_contains(strtolower($backup['filename']), $search))
            ->when(in_array($sortBy, ['filename', 'size', 'created_at']), fn ($collection) => $collection->sortBy(
                $sortBy,
                SORT_REGULAR,
                $sortDirection === 'desc'
            ))
            ->values();

        $paginator = new LengthAwarePaginator(
            $backups->forPage($page, $perPage)->values(),
            $backups->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $paginator->withQueryString();

        return Inertia::render('Backups/Index', [
            'backups' => $paginator,
            'settings' => BackupSetting::current(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'backup_enabled' => 'sometimes|boolean',
            'backup_time' => 'required|date_format:H:i',
            'backup_retention_days' => 'required|integer|min:1|max:365',
            'monthly_backup_enabled' => 'sometimes|boolean',
            'monthly_backup_retention_months' => 'required|integer|min:1|max:120',
        ]);

        $settings = BackupSetting::current();

        $settings->update([
            'backup_enabled' => $request->boolean('backup_enabled', $settings->backup_enabled),
            'backup_time' => $request->input('backup_time', $settings->backup_time),
            'backup_retention_days' => (int) $request->input('backup_retention_days', $settings->backup_retention_days),
            'monthly_backup_enabled' => $request->boolean('monthly_backup_enabled', $settings->monthly_backup_enabled),
            'monthly_backup_retention_months' => (int) $request->input('monthly_backup_retention_months', $settings->monthly_backup_retention_months),
        ]);

        activity()
            ->event('updated')
            ->causedBy($request->user())
            ->withProperties([
                'backup_enabled' => $settings->backup_enabled,
                'backup_time' => $settings->backup_time,
                'backup_retention_days' => $settings->backup_retention_days,
                'monthly_backup_enabled' => $settings->monthly_backup_enabled,
                'monthly_backup_retention_months' => $settings->monthly_backup_retention_months,
            ])
            ->log('Database backup settings updated');

        session()->flash('success', 'Backup settings updated successfully.');

        return redirect()->route('admin.backups.index');
    }

    public function store(): RedirectResponse
    {
        activity()
            ->event('created')
            ->causedBy(auth()->user())
            ->log('Database backup requested');

        try {
            $exitCode = Artisan::call('backup:run', [
                '--only-db' => true,
                '--disable-notifications' => true,
                '--no-interaction' => true,
            ]);

            if ($exitCode !== 0) {
                throw new \RuntimeException("The backup command exited with code {$exitCode}.");
            }
        } catch (Throwable $e) {
            activity()
                ->event('failed')
                ->causedBy(auth()->user())
                ->withProperties(['error' => $e->getMessage()])
                ->log('Database backup failed');

            session()->flash('error', 'Backup failed: '.$e->getMessage());

            return redirect()->route('admin.backups.index');
        }

        activity()
            ->event('created')
            ->causedBy(auth()->user())
            ->log('Database backup created');

        session()->flash('success', 'Database backup created successfully.');

        return redirect()->route('admin.backups.index');
    }

    public function download(string $filename): RedirectResponse|HttpFoundationResponse
    {
        $backup = $this->destination()->backups()
            ->first(fn ($backup) => basename($backup->path()) === $filename);

        if (! $backup || ! $backup->exists()) {
            session()->flash('error', 'Backup file not found.');

            return redirect()->route('admin.backups.index');
        }

        activity()
            ->event('downloaded')
            ->causedBy(auth()->user())
            ->log('Database backup downloaded' . ($backup ? ' (' . $backup->path() . ')' : ''));

        return $backup->disk()->download($backup->path());
    }

    public function destroy(string $filename): RedirectResponse
    {
        $backup = $this->destination()->backups()
            ->first(fn ($backup) => basename($backup->path()) === $filename);

        if (! $backup || ! $backup->exists()) {
            session()->flash('error', 'Backup file not found.');

            return redirect()->route('admin.backups.index');
        }

        activity()
            ->event('deleted')
            ->causedBy(auth()->user())
            ->log('Database backup deleted' . ($backup ? ' (' . $backup->path() . ')' : ''));
            
        $backup->disk()->delete($backup->path());

        session()->flash('success', 'Backup deleted successfully.');

        return redirect()->route('admin.backups.index');
    }

    private function destination(): BackupDestination
    {
        return BackupDestination::create('local', config('backup.backup.name'));
    }
}
