<?php

namespace App\Http\Controllers\Admin\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Website\UpdateSiteSettingRequest;
use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:manage website content')]
class SiteSettingController extends Controller
{
    public function __construct(protected SiteSettingsService $siteSettings) {}

    public function edit(): Response
    {
        return Inertia::render('Admin/Website/Settings', [
            'settings' => SiteSetting::query()->firstOrCreate([]),
        ]);
    }

    public function update(UpdateSiteSettingRequest $request): RedirectResponse
    {
        SiteSetting::query()->firstOrCreate([])->update($request->validated());

        $this->siteSettings->forget();

        return back()->with('success', 'Site settings updated.');
    }
}
