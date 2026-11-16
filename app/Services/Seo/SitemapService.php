<?php

namespace App\Services\Seo;

use App\Models\NewsPost;
use App\Models\Program;
use Illuminate\Support\Carbon;

class SitemapService
{
    /**
     * Named routes for the static public pages, with a relative priority.
     *
     * @var array<string, string>
     */
    protected array $staticRoutes = [
        'home' => '1.0',
        'about' => '0.8',
        'programs' => '0.9',
        'impact' => '0.8',
        'get-involved' => '0.9',
        'news' => '0.7',
        'contact' => '0.6',
    ];

    /**
     * Build the sitemap URL entries.
     *
     * @return array<int, array{loc: string, lastmod: ?string, priority: string}>
     */
    public function urls(): array
    {
        $urls = [];

        foreach ($this->staticRoutes as $name => $priority) {
            $urls[] = [
                'loc' => route($name),
                'lastmod' => null,
                'priority' => $priority,
            ];
        }

        Program::query()
            ->orderBy('display_order')
            ->get(['slug', 'updated_at'])
            ->each(function (Program $program) use (&$urls): void {
                $urls[] = [
                    'loc' => route('programs.show', $program->slug),
                    'lastmod' => $program->updated_at?->toAtomString(),
                    'priority' => '0.7',
                ];
            });

        NewsPost::query()
            ->where('is_published', true)
            ->latest('published_at')
            ->get(['id', 'updated_at'])
            ->each(function (NewsPost $post) use (&$urls): void {
                $urls[] = [
                    'loc' => route('news').'#post-'.$post->id,
                    'lastmod' => $post->updated_at?->toAtomString(),
                    'priority' => '0.6',
                ];
            });

        return $urls;
    }

    /**
     * Render the sitemap as an XML string.
     */
    public function toXml(): string
    {
        $now = Carbon::now()->toAtomString();

        $entries = collect($this->urls())
            ->map(function (array $url) use ($now): string {
                $lastmod = $url['lastmod'] ?? $now;

                return '<url>'
                    .'<loc>'.e($url['loc']).'</loc>'
                    .'<lastmod>'.$lastmod.'</lastmod>'
                    .'<priority>'.$url['priority'].'</priority>'
                    .'</url>';
            })
            ->implode('');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .$entries
            .'</urlset>';
    }
}
