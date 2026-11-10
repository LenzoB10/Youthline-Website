<?php

namespace App\Http\Controllers\Admin\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Website\NewsPostRequest;
use App\Models\Consent;
use App\Models\NewsPost;
use App\Models\Student;
use App\Services\Website\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:manage website content')]
class NewsPostController extends Controller
{
    public function __construct(protected ImageUploadService $images) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Website/News/Index', [
            'posts' => NewsPost::query()
                ->latest('created_at')
                ->get(['id', 'title', 'category', 'is_published', 'published_at', 'student_id']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Website/News/Form', [
            'students' => $this->consentedStudents(),
        ]);
    }

    public function store(NewsPostRequest $request): RedirectResponse
    {
        NewsPost::create($this->payload($request));

        return redirect()
            ->route('admin.website.news.index')
            ->with('success', 'News post created.');
    }

    public function edit(NewsPost $news): Response
    {
        return Inertia::render('Admin/Website/News/Form', [
            'post' => $news->only(['id', 'title', 'category', 'body', 'image', 'student_id', 'is_published']),
            'students' => $this->consentedStudents(),
        ]);
    }

    public function update(NewsPostRequest $request, NewsPost $news): RedirectResponse
    {
        $news->update($this->payload($request, $news));

        return redirect()
            ->route('admin.website.news.index')
            ->with('success', 'News post updated.');
    }

    public function destroy(NewsPost $news): RedirectResponse
    {
        $news->delete();

        return back()->with('success', 'News post deleted.');
    }

    public function togglePublish(NewsPost $news): RedirectResponse
    {
        if (! $news->is_published && $this->blockedByConsent($news)) {
            return back()->with('error', 'This student has not consented to their story being published.');
        }

        $news->update([
            'is_published' => ! $news->is_published,
            'published_at' => $news->is_published ? $news->published_at : now(),
        ]);

        return back()->with('success', $news->is_published ? 'Post published.' : 'Post unpublished.');
    }

    /**
     * Build the persisted attributes from the validated request.
     *
     * @return array<string, mixed>
     */
    protected function payload(NewsPostRequest $request, ?NewsPost $news = null): array
    {
        $data = $request->safe()->except('image');
        $data['author_id'] = $request->user()->id;
        $data['image'] = $this->images->store($request->file('image'), 'news', $news?->image);

        if (($data['is_published'] ?? false) && empty($news?->published_at)) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function blockedByConsent(NewsPost $news): bool
    {
        if ($news->student_id === null) {
            return false;
        }

        return ! Consent::query()
            ->where('student_id', $news->student_id)
            ->where('story_allowed', true)
            ->exists();
    }

    /**
     * Students whose guardians have allowed their story to be published.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function consentedStudents(): Collection
    {
        return Student::query()
            ->whereHas('consent', fn ($query) => $query->where('story_allowed', true))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (Student $student): array => [
                'id' => $student->id,
                'name' => $student->first_name.' '.$student->last_name,
            ]);
    }
}
