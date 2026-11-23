<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateSystemSettingsRequest;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:update settings')]
class SystemSettingController extends Controller
{
    public function __construct(protected SystemSettingsService $settings) {}

    public function edit(): Response
    {
        $settings = $this->settings->get()->load('updatedBy:id,name');

        return Inertia::render('Admin/Settings/Approvals', [
            'settings' => [
                ...$settings->only([
                    'currency',
                    'require_scholarship_approval',
                    'counselling_notes_visibility', 'grade_drop_threshold',
                    'mentorship_overdue_days', 'home_visit_interval_months', 'business_checkin_days',
                ]),
                'updated_by' => $settings->updatedBy?->name,
                'updated_at' => $settings->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function update(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        $this->settings->update($request->validated(), $request->user());

        return back()->with('success', 'Approval settings saved.');
    }
}
