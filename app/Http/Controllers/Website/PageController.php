<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoService;
use App\Services\Website\ImpactMetricsService;
use App\Services\Website\PublicContentService;
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
        return $this->page('Public/About', 'about', $this->content->about());
    }

    public function programs(): Response
    {
        return $this->page('Public/Programs', 'programs', $this->content->programsPage());
    }

    public function impact(): Response
    {
        return $this->page('Public/Impact', 'impact', [
            'metrics' => $this->impact->all(),
        ]);
    }

    public function getInvolved(): Response
    {
        return $this->page('Public/GetInvolved', 'get-involved');
    }

    public function news(): Response
    {
        return $this->page('Public/News', 'news', $this->content->newsPage());
    }

    public function contact(): Response
    {
        return $this->page('Public/Contact', 'contact', $this->content->contact());
    }

    /**
     * Render a public page with its SEO payload and content props.
     *
     * @param  array<string, mixed>  $props
     */
    protected function page(string $component, string $seoKey, array $props = []): Response
    {
        return Inertia::render($component, [
            'seo' => $this->seo->forPage($seoKey),
            ...$props,
        ]);
    }
}
