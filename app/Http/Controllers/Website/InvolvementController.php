<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\StoreInvolvementRequest;
use App\Models\InvolvementRequest;
use Illuminate\Http\RedirectResponse;

class InvolvementController extends Controller
{
    public function __invoke(StoreInvolvementRequest $request): RedirectResponse
    {
        if (blank($request->input('company'))) {
            InvolvementRequest::create($request->safe()->except('company') + ['status' => 'new']);
        }

        return back()->with('success', 'Thank you for your interest — the Youthline team will reach out to you.');
    }
}
