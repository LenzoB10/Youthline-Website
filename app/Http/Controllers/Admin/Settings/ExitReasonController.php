<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\ExitReasonRequest;
use App\Http\Requests\DatatableRequest;
use App\Models\ExitReason;
use App\Traits\HasDataTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:update settings')]
class ExitReasonController extends Controller
{
    use HasDataTable;

    public function index(DatatableRequest $request): Response
    {
        $this->restrictSorting($request, ['name', 'display_order', 'is_active'], 'display_order');

        $reasons = $this->processDataTable(
            ExitReason::query(),
            $request,
            ['name', 'description'],
            ['is_active' => fn ($query, $value) => $query->where('is_active', $value === 'active')],
            defaultSortBy: 'display_order',
            defaultSortDirection: 'asc',
        );

        $reasons->getCollection()->transform(fn (ExitReason $reason) => [
            ...$reason->only(['id', 'name', 'description', 'display_order', 'is_active']),
            'students_count' => $reason->studentsCount(),
        ]);

        return Inertia::render('Admin/Settings/ExitReasons/Index', [
            'reasons' => $reasons->appends($request->query()),
            'filters' => $request->all(),
        ]);
    }

    public function store(ExitReasonRequest $request): RedirectResponse
    {
        ExitReason::create($request->validated());

        return back()->with('success', 'Exit reason added.');
    }

    public function update(ExitReasonRequest $request, ExitReason $exitReason): RedirectResponse
    {
        $exitReason->update($request->validated());

        return back()->with('success', 'Exit reason updated.');
    }

    public function destroy(ExitReason $exitReason): RedirectResponse
    {
        if ($exitReason->studentsCount() > 0) {
            return back()->with('error', 'This reason is recorded for students who have left. Deactivate it instead so it stops appearing as an option.');
        }

        $exitReason->delete();

        return back()->with('success', 'Exit reason deleted.');
    }
}
