<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\ItemCategoryRequest;
use App\Http\Requests\DatatableRequest;
use App\Models\ItemCategory;
use App\Services\Settings\SystemSettingsService;
use App\Traits\HasDataTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:update settings')]
class ItemCategoryController extends Controller
{
    use HasDataTable;

    public function index(DatatableRequest $request, SystemSettingsService $settings): Response
    {
        $this->restrictSorting($request, ['name', 'unit_cost', 'distributions_count'], 'name');

        $categories = $this->processDataTable(
            ItemCategory::query()->withCount('distributions'),
            $request,
            ['name'],
            defaultSortBy: 'name',
            defaultSortDirection: 'asc',
        );

        return Inertia::render('Admin/Settings/ItemCategories/Index', [
            'categories' => $categories->appends($request->query()),
            'filters' => $request->all(),
            'currency' => $settings->get()->currency,
        ]);
    }

    public function store(ItemCategoryRequest $request): RedirectResponse
    {
        ItemCategory::create($request->validated());

        return back()->with('success', 'Item category added.');
    }

    public function update(ItemCategoryRequest $request, ItemCategory $itemCategory): RedirectResponse
    {
        $itemCategory->update($request->validated());

        return back()->with('success', 'Item category updated.');
    }

    public function destroy(ItemCategory $itemCategory): RedirectResponse
    {
        if ($itemCategory->isInUse()) {
            return back()->with('error', 'Items in this category have already been distributed, so it cannot be deleted.');
        }

        $itemCategory->delete();

        return back()->with('success', 'Item category deleted.');
    }
}
