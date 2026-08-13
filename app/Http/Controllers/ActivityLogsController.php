<?php

namespace App\Http\Controllers;

use App\Traits\HasDataTable;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

#[Middleware('permission:list audit logs', only: ['index'])]
class ActivityLogsController extends Controller
{
    use HasDataTable;

    public function index(Request $request): Response
    {
        $query = Activity::with('causer')
            ->select([
                'activity_log.*',
                'users.name as causer_name',
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
