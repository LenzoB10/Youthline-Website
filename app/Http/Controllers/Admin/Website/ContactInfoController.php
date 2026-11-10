<?php

namespace App\Http\Controllers\Admin\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Website\UpdateContactInfoRequest;
use App\Models\ContactInfo;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:manage website content')]
class ContactInfoController extends Controller
{
    public function __construct(protected SiteSettingsService $siteSettings) {}

    public function edit(): Response
    {
        return Inertia::render('Admin/Website/Contact', [
            'contact' => ContactInfo::query()->firstOrCreate([]),
        ]);
    }

    public function update(UpdateContactInfoRequest $request): RedirectResponse
    {
        ContactInfo::query()->firstOrCreate([])->update($request->validated());

        $this->siteSettings->forget();

        return back()->with('success', 'Contact details updated.');
    }
}
