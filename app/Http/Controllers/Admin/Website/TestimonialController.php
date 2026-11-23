<?php

namespace App\Http\Controllers\Admin\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Website\TestimonialRequest;
use App\Models\Testimonial;
use App\Services\Website\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:manage website content')]
class TestimonialController extends Controller
{
    public function __construct(protected ImageUploadService $images) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Website/Testimonials/Index', [
            'testimonials' => Testimonial::query()
                ->orderBy('display_order')
                ->orderBy('id')
                ->get(['id', 'name', 'role', 'quote', 'photo', 'is_published', 'display_order']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Website/Testimonials/Form');
    }

    public function store(TestimonialRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('photo');
        $data['photo'] = $this->images->store($request->file('photo'), 'testimonials');

        Testimonial::create($data);

        return redirect()->route('admin.website.testimonials.index')->with('success', 'Testimonial added.');
    }

    public function edit(Testimonial $testimonial): Response
    {
        return Inertia::render('Admin/Website/Testimonials/Form', [
            'testimonial' => $testimonial->only(['id', 'name', 'role', 'quote', 'photo', 'is_published', 'display_order']),
        ]);
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $data = $request->safe()->except('photo');
        $data['photo'] = $this->images->store($request->file('photo'), 'testimonials', $testimonial->photo);

        $testimonial->update($data);

        return redirect()->route('admin.website.testimonials.index')->with('success', 'Testimonial updated.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $this->images->delete($testimonial->photo);
        $testimonial->delete();

        return back()->with('success', 'Testimonial removed.');
    }
}
