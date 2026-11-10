<?php

namespace App\Http\Controllers\Admin\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Website\CoreValueRequest;
use App\Models\CoreValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:manage website content')]
class CoreValueController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Website/Values/Index', [
            'values' => CoreValue::query()
                ->orderBy('display_order')
                ->get(['id', 'name', 'description', 'display_order']),
        ]);
    }

    public function store(CoreValueRequest $request): RedirectResponse
    {
        CoreValue::create($request->validated());

        return back()->with('success', 'Value added.');
    }

    public function update(CoreValueRequest $request, CoreValue $value): RedirectResponse
    {
        $value->update($request->validated());

        return back()->with('success', 'Value updated.');
    }

    public function destroy(CoreValue $value): RedirectResponse
    {
        $value->delete();

        return back()->with('success', 'Value deleted.');
    }
}
