<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\EducationLevel;
use App\Http\Controllers\Controller;
use App\Models\ExitReason;
use App\Models\Institution;
use App\Models\ItemCategory;
use App\Models\Program;
use App\Services\Settings\AcademicCalendarService;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:update settings')]
class SettingsController extends Controller
{
    public function index(AcademicCalendarService $calendar): Response
    {
        $year = $calendar->current();
        $term = $calendar->currentTerm();

        return Inertia::render('Admin/Settings/Index', [
            'currentYear' => $year?->only(['id', 'name']),
            'currentTerm' => $term?->only(['id', 'name']),
            'counts' => [
                'institutions' => Institution::query()->count(),
                'programs' => Program::query()->count(),
                'item_categories' => ItemCategory::query()->count(),
                'exit_reasons' => ExitReason::query()->where('is_active', true)->count(),
            ],
            'levels' => EducationLevel::options(),
        ]);
    }
}
