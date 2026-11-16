<?php

namespace App\Services;

use App\Services\Seo\SeoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders friendly error pages.
 *
 * Website visitors get a branded public page (header, footer, links back into the
 * site). Signed-in staff on admin screens keep the admin-layout error page.
 */
class ErrorPageService
{
    /** @var array<int, int> */
    public const HANDLED = [403, 404, 500, 503];

    /** @var array<int, string> */
    protected const PUBLIC_PATHS = ['/', 'about', 'programs', 'programs/*', 'impact', 'get-involved', 'news', 'news/*', 'contact'];

    /** @var array<int, string> */
    protected const TITLES = [
        403 => 'Access denied',
        404 => 'Page not found',
        500 => 'Something went wrong',
        503 => 'Down for maintenance',
    ];

    public function __construct(
        protected SiteSettingsService $siteSettings,
        protected SeoService $seo,
    ) {}

    public function handles(int $status): bool
    {
        return in_array($status, self::HANDLED, true);
    }

    public function render(int $status, Request $request): Response
    {
        $page = $this->isAdminRequest($request)
            ? Inertia::render('Errors/Index', ['status' => $status])
            : Inertia::render('Public/Error', [
                'status' => $status,
                // Shared props are missing when no route matched, so pass branding explicitly.
                'site' => $this->siteSettings->data(),
                'seo' => $this->seo->forPage('home', [
                    'title' => self::TITLES[$status],
                    'robots' => 'noindex, nofollow',
                ]),
            ]);

        return $page->toResponse($request)->setStatusCode($status);
    }

    protected function isAdminRequest(Request $request): bool
    {
        return $request->user() !== null && ! $request->is(...self::PUBLIC_PATHS);
    }
}
