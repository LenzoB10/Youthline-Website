<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Traits\HasDataTable;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Activitylog\Models\Activity;

class ActivityLogsController extends Controller implements HasMiddleware
{
    use HasDataTable;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:list audit logs', only: ['index']),
        ];
    }

    public function index(Request $request)
    {
        $query = Activity::with('causer')
            ->select([
                'activity_log.*',
                'users.name as causer_name'
            ])
            ->leftJoin('users', 'activity_log.causer_id', '=', 'users.id');

        $activityLogs = $this->processDataTable(
            $query,
            $request,
            ['description', 'event', 'subject_type', 'users.name'],
        );

        $activityLogs->appends($request->query());

        return Inertia::render('ActivityLogs/Index', [
            'activityLogs' => $activityLogs,
        ]);
    }
}
