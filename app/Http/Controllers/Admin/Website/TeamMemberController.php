<?php

namespace App\Http\Controllers\Admin\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Website\TeamMemberRequest;
use App\Models\TeamMember;
use App\Services\Website\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:manage website content')]
class TeamMemberController extends Controller
{
    public function __construct(protected ImageUploadService $images) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Website/Team/Index', [
            'members' => TeamMember::query()
                ->orderBy('display_order')
                ->get(['id', 'name', 'position', 'photo', 'display_order']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Website/Team/Form');
    }

    public function store(TeamMemberRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('photo');
        $data['photo'] = $this->images->store($request->file('photo'), 'team');

        TeamMember::create($data);

        return redirect()->route('admin.website.team.index')->with('success', 'Team member added.');
    }

    public function edit(TeamMember $team): Response
    {
        return Inertia::render('Admin/Website/Team/Form', [
            'member' => $team->only(['id', 'name', 'position', 'bio', 'photo', 'display_order']),
        ]);
    }

    public function update(TeamMemberRequest $request, TeamMember $team): RedirectResponse
    {
        $data = $request->safe()->except('photo');
        $data['photo'] = $this->images->store($request->file('photo'), 'team', $team->photo);

        $team->update($data);

        return redirect()->route('admin.website.team.index')->with('success', 'Team member updated.');
    }

    public function destroy(TeamMember $team): RedirectResponse
    {
        $this->images->delete($team->photo);
        $team->delete();

        return back()->with('success', 'Team member removed.');
    }
}
