<?php

namespace App\Http\Controllers\Admin\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Website\PartnerRequest;
use App\Models\Partner;
use App\Services\Website\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:manage website content')]
class PartnerController extends Controller
{
    public function __construct(protected ImageUploadService $images) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Website/Partners/Index', [
            'partners' => Partner::query()
                ->orderBy('display_order')
                ->orderBy('id')
                ->get(['id', 'name', 'logo', 'website_url', 'is_published', 'display_order']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Website/Partners/Form');
    }

    public function store(PartnerRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('logo');
        $data['logo'] = $this->images->store($request->file('logo'), 'partners');

        Partner::create($data);

        return redirect()->route('admin.website.partners.index')->with('success', 'Partner added.');
    }

    public function edit(Partner $partner): Response
    {
        return Inertia::render('Admin/Website/Partners/Form', [
            'partner' => $partner->only(['id', 'name', 'logo', 'website_url', 'is_published', 'display_order']),
        ]);
    }

    public function update(PartnerRequest $request, Partner $partner): RedirectResponse
    {
        $data = $request->safe()->except('logo');
        $data['logo'] = $this->images->store($request->file('logo'), 'partners', $partner->logo);

        $partner->update($data);

        return redirect()->route('admin.website.partners.index')->with('success', 'Partner updated.');
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        $this->images->delete($partner->logo);
        $partner->delete();

        return back()->with('success', 'Partner removed.');
    }
}
