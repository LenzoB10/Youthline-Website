<?php

namespace App\Http\Controllers\Admin\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Website\UpdateInvolvementRequestStatusRequest;
use App\Models\InvolvementRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:manage website content')]
class InvolvementRequestController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Website/Involvement/Index', [
            'requests' => InvolvementRequest::query()
                ->latest()
                ->get(['id', 'name', 'email', 'phone', 'type', 'message', 'status', 'created_at']),
        ]);
    }

    public function update(UpdateInvolvementRequestStatusRequest $request, InvolvementRequest $involvement): RedirectResponse
    {
        $involvement->update($request->validated());

        return back()->with('success', 'Request status updated.');
    }

    public function destroy(InvolvementRequest $involvement): RedirectResponse
    {
        $involvement->delete();

        return back()->with('success', 'Request deleted.');
    }
}
