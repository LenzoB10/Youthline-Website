<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\InstitutionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\InstitutionRequest;
use App\Http\Requests\DatatableRequest;
use App\Models\Institution;
use App\Traits\HasDataTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:update settings')]
class InstitutionController extends Controller
{
    use HasDataTable;

    public function index(DatatableRequest $request): Response
    {
        $this->restrictSorting($request, ['name', 'type', 'district', 'enrollments_count'], 'name');

        $institutions = $this->processDataTable(
            Institution::query()->withCount('enrollments'),
            $request,
            ['name', 'district'],
            ['type' => 'type'],
            defaultSortBy: 'name',
            defaultSortDirection: 'asc',
        );

        return Inertia::render('Admin/Settings/Institutions/Index', [
            'institutions' => $institutions->appends($request->query()),
            'filters' => $request->all(),
            'types' => InstitutionType::options(),
        ]);
    }

    public function store(InstitutionRequest $request): RedirectResponse
    {
        Institution::create($request->validated());

        return back()->with('success', 'Institution added.');
    }

    public function update(InstitutionRequest $request, Institution $institution): RedirectResponse
    {
        $institution->update($request->validated());

        return back()->with('success', 'Institution updated.');
    }

    public function destroy(Institution $institution): RedirectResponse
    {
        if ($institution->isInUse()) {
            return back()->with('error', 'This institution is part of student enrolment or fee records, so it cannot be deleted.');
        }

        $institution->delete();

        return back()->with('success', 'Institution deleted.');
    }
}
