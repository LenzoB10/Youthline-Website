<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Services\Seo\SeoService;
use App\Services\Website\ImpactMetricsService;
use App\Services\Website\PublicContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders the public-facing Youthline website. Page content is assembled by the
 * PublicContentService; this controller only maps requests to components.
 */
class PageController extends Controller
{
    public function __construct(
        protected SeoService $seo,
        protected PublicContentService $content,
        protected ImpactMetricsService $impact,
    ) {}

    public function home(): Response
    {
        return $this->page('Public/Home', 'home', [
            ...$this->content->home(),
            'impact' => $this->impact->headline(),
        ]);
    }

    public function about(): Response
    {
        return $this->page('Public/About', 'about', [
            ...$this->content->about(),
            'impact' => $this->impact->headline(),
        ]);
    }

    public function programs(): Response
    {
        return $this->page('Public/Programs', 'programs', $this->content->programsPage());
    }

    public function program(Program $program): Response
    {
        $content = $this->content->programPage($program);

        return $this->page('Public/ProgramShow', 'programs', $content, [
            'title' => $program->name,
            'description' => Str::limit(strip_tags((string) ($program->summary ?: $program->description)), 160),
            'image' => url($content['program']['image']),
        ]);
    }

    public function getInvolved(): Response
    {
        return $this->page('Public/GetInvolved', 'get-involved');
    }

    public function news(Request $request): Response
    {
        return $this->page('Public/News', 'news', $this->content->newsPage($request->string('category')->toString() ?: null));
    }

    public function contact(): Response
    {
        return $this->page('Public/Contact', 'contact', $this->content->contact());
    }

    /**
     * Render a public page with its SEO payload and content props.
     *
     * @param  array<string, mixed>  $props
     * @param  array<string, string>  $seo  Per-page SEO overrides
     */
    protected function page(string $component, string $seoKey, array $props = [], array $seo = []): Response
    {
        return Inertia::render($component, [
            'seo' => $this->seo->forPage($seoKey, $seo),
            ...$props,
        ]);
    }
}
