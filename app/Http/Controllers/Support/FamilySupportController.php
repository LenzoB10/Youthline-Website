<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\FamilySupportRequest;
use App\Models\FamilySupport;
use App\Models\Household;
use App\Services\Support\FamilySupportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;

/**
 * Support given to a family (Support → Families). Routes use scoped bindings,
 * so an entry must belong to the family in the URL.
 */
#[Middleware('permission:manage home visits', only: ['store'])]
#[Middleware('permission:delete support records', only: ['destroy'])]
class FamilySupportController extends Controller
{
    public function __construct(protected FamilySupportService $support) {}

    public function store(FamilySupportRequest $request, Household $household): RedirectResponse
    {
        $this->support->record($household, $request->validated(), $request->file('photos', []), $request->user());

        return back()->with('success', 'Support recorded.');
    }

    public function destroy(Household $household, FamilySupport $familySupport): RedirectResponse
    {
        $this->support->delete($familySupport);

        return back()->with('success', 'Entry deleted.');
    }
}
