<?php

namespace App\Http\Controllers\Admin\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Website\UpdatePageRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:manage website content')]
class PagesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Website/Pages/Index', [
            'pages' => Page::query()
                ->orderBy('id')
                ->get(['id', 'key', 'title', 'summary', 'updated_at']),
        ]);
    }

    public function edit(Page $page): Response
    {
        return Inertia::render('Admin/Website/Pages/Edit', [
            'page' => $page->only(['id', 'key', 'title', 'summary', 'body']),
        ]);
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $page->update($request->validated() + ['updated_by' => $request->user()->id]);

        return redirect()
            ->route('admin.website.pages.index')
            ->with('success', 'Page content updated.');
    }
}
