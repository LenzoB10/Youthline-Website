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
    /**
     * Photos used for programs that have no uploaded image yet.
     */
    public const FALLBACK_PROGRAM_PHOTOS = [
        '/imgs/web/md/IMG20261028134003.jpg',
        '/imgs/web/md/IMG20261028134004.jpg',
        '/imgs/web/md/IMG20261028134006.jpg',
        '/imgs/web/md/IMG20261028134007.jpg',
        '/imgs/web/md/IMG20261028134008.jpg',
        '/imgs/web/md/IMG20261028134009.jpg',
        '/imgs/web/md/IMG20261028134049.jpg',
        '/imgs/web/md/IMG20261028134052.jpg',
    ];

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
            'news' => $this->newsList(4),
        ];
    }

    /**
     * Content for the News listing page.
     *
     * @return array<string, mixed>
     */
    public function newsPage(?string $category = null): array
    {
        // Unknown categories are ignored rather than showing an empty page.
        $category = array_key_exists((string) $category, NewsPost::CATEGORIES) ? $category : null;

        $counts = NewsPost::query()
            ->publiclyVisible()
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return [
            'page' => $this->page('news'),
            'posts' => $this->newsList(category: $category),
            'activeCategory' => $category,
            'categories' => collect(NewsPost::CATEGORIES)
                ->map(fn (string $label, string $key): array => [
                    'key' => $key,
                    'label' => $label,
                    'count' => (int) ($counts[$key] ?? 0),
                ])
                ->values(),
            'totalPosts' => (int) $counts->sum(),
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
     * Content for a single program's page, plus the other programs to explore.
     *
     * @return array<string, mixed>
     */
    public function programPage(Program $program): array
    {
        $all = $this->programs();

        return [
            'program' => $all->firstWhere('id', $program->id),
            'otherPrograms' => $all->reject(fn (array $item) => $item['id'] === $program->id)->values(),
        ];
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
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'summary', 'description', 'image'])
            ->values()
            ->map(fn (Program $program, int $index): array => $this->mapProgram($program, $counts[$program->id] ?? 0, $index));
    }

    /**
     * Shape a program for the frontend. Programs without an uploaded photo
     * fall back to one of the site's own photos so every card has a picture.
     *
     * @return array<string, mixed>
     */
    protected function mapProgram(Program $program, int $studentCount, int $index): array
    {
        return [
            'id' => $program->id,
            'name' => $program->name,
            'slug' => $program->slug,
            'summary' => $program->summary,
            'description' => $program->description,
            'image' => $program->image ?: self::FALLBACK_PROGRAM_PHOTOS[$index % count(self::FALLBACK_PROGRAM_PHOTOS)],
            'has_own_image' => filled($program->image),
            'student_count' => $studentCount,
        ];
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
    protected function newsList(?int $limit = null, ?string $category = null): Collection
    {
        return NewsPost::query()
            ->publiclyVisible()
            ->when($category, fn ($query) => $query->where('category', $category))
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
            'published_long' => $post->published_at?->translatedFormat('F j, Y'),
            'published_relative' => $post->published_at?->diffForHumans(),
            'excerpt' => Str::limit(strip_tags((string) $post->body), 160),
            // Roughly 200 words a minute, never less than one minute.
            'reading_minutes' => max(1, (int) ceil(str_word_count(strip_tags((string) $post->body)) / 200)),
            'body' => $full ? $post->body : null,
        ];
    }
}
