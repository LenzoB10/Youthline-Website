<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\HomeVisitRequest;
use App\Models\HomeVisit;
use App\Models\Household;
use App\Services\Support\HomeVisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;

/**
 * Home visits belong to a family (Support → Families). Routes use scoped
 * bindings, so a visit must belong to the family in the URL.
 */
#[Middleware('permission:manage home visits', only: ['storeVisit'])]
#[Middleware('permission:delete support records', only: ['destroyVisit'])]
class HomeVisitController extends Controller
{
    public function __construct(
        protected HomeVisitService $visits,
    ) {}

    public function storeVisit(HomeVisitRequest $request, Household $household): RedirectResponse
    {
        $this->visits->record($household, $request->validated(), $request->file('photos', []), $request->user());

        return back()->with('success', 'Home visit recorded.');
    }

    public function destroyVisit(Household $household, HomeVisit $homeVisit): RedirectResponse
    {
        $this->visits->delete($homeVisit);

        return back()->with('success', 'Visit deleted.');
    }
}
