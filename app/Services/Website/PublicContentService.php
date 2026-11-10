<?php

namespace App\Services\Website;

use App\Models\CoreValue;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Program;
use App\Models\TeamMember;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicContentService
{
    public function __construct(protected ImpactMetricsService $metrics) {}

    /**
     * Content for the Home page: intro previews of the other pages.
     *
     * @return array<string, mixed>
     */
    public function home(): array
    {
        return [
            'intro' => $this->page('home'),
            'aboutSummary' => $this->page('about')['summary'] ?? null,
            'programs' => $this->programs(),
            'values' => $this->values(),
            'news' => $this->newsList(3),
        ];
    }

    /**
     * Content for the News listing page.
     *
     * @return array<string, mixed>
     */
    public function newsPage(): array
    {
        return [
            'page' => $this->page('news'),
            'posts' => $this->newsList(),
        ];
    }

    /**
     * Map a single, publicly-visible post for its detail page.
     *
     * @return array<string, mixed>
     */
    public function newsPost(NewsPost $post): array
    {
        return $this->mapPost($post, true);
    }

    /**
     * Content for the About page.
     *
     * @return array<string, mixed>
     */
    public function about(): array
    {
        return [
            'page' => $this->page('about'),
            'vision' => $this->page('vision'),
            'mission' => $this->page('mission'),
            'commitment' => $this->page('commitment'),
            'values' => $this->values(),
            'team' => $this->team(),
        ];
    }

    /**
     * Content for the Programs page.
     *
     * @return array<string, mixed>
     */
    public function programsPage(): array
    {
        return [
            'page' => $this->page('programs'),
            'programs' => $this->programs(),
        ];
    }

    /**
     * Content for the Contact page.
     *
     * @return array<string, mixed>
     */
    public function contact(): array
    {
        return [
            'page' => $this->page('contact'),
        ];
    }

    /**
     * A single editable page block by key.
     *
     * @return array{title: ?string, summary: ?string, body: ?string}|null
     */
    protected function page(string $key): ?array
    {
        $page = Page::query()->where('key', $key)->first();

        if ($page === null) {
            return null;
        }

        return [
            'title' => $page->title,
            'summary' => $page->summary,
            'body' => $page->body,
        ];
    }

    /**
     * The six core programs, ordered for display.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function programs(): Collection
    {
        $counts = $this->metrics->programStudentCounts();

        return Program::query()
            ->orderBy('display_order')
            ->get(['id', 'name', 'slug', 'summary', 'description'])
            ->map(fn (Program $program): array => [
                'id' => $program->id,
                'name' => $program->name,
                'slug' => $program->slug,
                'summary' => $program->summary,
                'description' => $program->description,
                'student_count' => $counts[$program->id] ?? 0,
            ]);
    }

    /**
     * The core values, ordered for display.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function values(): Collection
    {
        return CoreValue::query()
            ->orderBy('display_order')
            ->get(['id', 'name', 'description'])
            ->map(fn (CoreValue $value): array => [
                'id' => $value->id,
                'name' => $value->name,
                'description' => $value->description,
            ]);
    }

    /**
     * The leadership team, ordered for display.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function team(): Collection
    {
        return TeamMember::query()
            ->orderBy('display_order')
            ->get(['id', 'name', 'position', 'photo', 'bio'])
            ->map(fn (TeamMember $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'position' => $member->position,
                'photo' => $member->photo,
                'bio' => $member->bio,
            ]);
    }

    /**
     * Publicly-visible news posts, newest first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function newsList(?int $limit = null): Collection
    {
        return NewsPost::query()
            ->publiclyVisible()
            ->latest('published_at')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->get(['id', 'title', 'category', 'body', 'image', 'published_at'])
            ->map(fn (NewsPost $post): array => $this->mapPost($post));
    }

    /**
     * Shape a news post for the frontend.
     *
     * @return array<string, mixed>
     */
    protected function mapPost(NewsPost $post, bool $full = false): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'category' => $post->category,
            'image' => $post->image,
            'published_at' => $post->published_at?->toDateString(),
            'published_human' => $post->published_at?->translatedFormat('j M Y'),
            'excerpt' => Str::limit(strip_tags((string) $post->body), 160),
            'body' => $full ? $post->body : null,
        ];
    }
}
