<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\HouseholdConsentRequest;
use App\Http\Requests\Support\HouseholdRequest;
use App\Models\Household;
use App\Services\Settings\SystemSettingsService;
use App\Services\Support\FamilyService;
use App\Services\Support\SupportProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Support → Families: every family Youthline supports — students' homes and
 * community families with no student on the programme.
 */
#[Middleware('permission:manage home visits')]
class HouseholdController extends Controller
{
    public function __construct(
        protected FamilyService $families,
        protected SystemSettingsService $settings,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Support/Families', [
            'currency' => $this->settings->get()->currency,
            ...$this->families->overview(),
        ]);
    }

    public function store(HouseholdRequest $request): RedirectResponse
    {
        $household = $this->families->create($request->validated(), $request->user());

        return redirect()->route('support.families.show', $household)
            ->with('success', 'Family added. You can now record a home visit.');
    }

    public function show(Household $household, SupportProfileService $support): Response
    {
        return Inertia::render('Support/Household', [
            'currency' => $this->settings->get()->currency,
            ...$support->household($household),
        ]);
    }

    public function update(HouseholdRequest $request, Household $household): RedirectResponse
    {
        $household->update($request->validated());

        return back()->with('success', 'Family details saved.');
    }

    public function updateConsent(HouseholdConsentRequest $request, Household $household): RedirectResponse
    {
        $household->update([
            'photo_consent' => $request->boolean('photo_consent'),
            'photo_consent_on' => $request->boolean('photo_consent') ? $request->validated('photo_consent_on') : null,
        ]);

        return back()->with('success', $household->photo_consent ? 'Photo consent recorded.' : 'Photo consent withdrawn.');
    }
}
