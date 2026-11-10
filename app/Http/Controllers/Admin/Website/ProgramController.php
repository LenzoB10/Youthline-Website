<?php

namespace App\Http\Controllers\Admin\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Website\ProgramRequest;
use App\Models\Program;
use App\Services\Website\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:manage website content')]
class ProgramController extends Controller
{
    public function __construct(protected ImageUploadService $images) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Website/Programs/Index', [
            'programs' => Program::query()
                ->orderBy('display_order')
                ->withCount('students')
                ->get(['id', 'name', 'summary', 'image', 'display_order']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Website/Programs/Form');
    }

    public function store(ProgramRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['slug'] = $this->uniqueSlug($request->validated('name'));
        $data['image'] = $this->images->store($request->file('image'), 'programs');

        Program::create($data);

        return redirect()->route('admin.website.programs.index')->with('success', 'Program created.');
    }

    public function edit(Program $program): Response
    {
        return Inertia::render('Admin/Website/Programs/Form', [
            'program' => $program->only(['id', 'name', 'summary', 'description', 'image', 'display_order']),
        ]);
    }

    public function update(ProgramRequest $request, Program $program): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['image'] = $this->images->store($request->file('image'), 'programs', $program->image);

        $program->update($data);

        return redirect()->route('admin.website.programs.index')->with('success', 'Program updated.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        $this->images->delete($program->image);
        $program->delete();

        return back()->with('success', 'Program deleted.');
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (Program::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
