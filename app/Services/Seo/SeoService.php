<?php

namespace App\Services\Seo;

use App\Services\SiteSettingsService;

class SeoService
{
    public function __construct(protected SiteSettingsService $siteSettings) {}

    /**
     * Default title/description per public page, kept in one place.
     *
     * @var array<string, array{title: string, description: string}>
     */
    protected array $pages = [
        'home' => [
            'title' => 'Home',
            'description' => 'Youthline helps vulnerable young people in Malawi access education, essential resources, mentorship and opportunity to build brighter futures.',
        ],
        'about' => [
            'title' => 'About Us',
            'description' => 'Youthline is a youth empowerment and community development organization improving the lives and futures of vulnerable and underserved young people.',
        ],
        'programs' => [
            'title' => 'Our Programs',
            'description' => 'Six connected programs — education, basic needs, mentorship, careers, family support and entrepreneurship — that support a young person’s whole journey.',
        ],
        'get-involved' => [
            'title' => 'Get Involved',
            'description' => 'Volunteer, mentor, partner, sponsor a student, or donate — there are many ways to help a young person thrive with Youthline.',
        ],
        'news' => [
            'title' => 'News and Stories',
            'description' => 'Updates, success stories and activities from Youthline and the young people we serve.',
        ],
        'contact' => [
            'title' => 'Contact Us',
            'description' => 'Get in touch with the Youthline team — we would love to hear from you.',
        ],
    ];

    /**
     * Build the SEO payload for a page, merging sensible defaults with overrides.
     *
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    public function forPage(string $key, array $overrides = []): array
    {
        $defaults = $this->pages[$key] ?? $this->pages['home'];
        $site = $this->siteSettings->data();

        $title = $overrides['title'] ?? $defaults['title'];
        $description = $overrides['description'] ?? $defaults['description'];

        return [
            'title' => $title,
            'full_title' => $title.' - '.$site['org_name'],
            'description' => $description,
            'canonical' => $overrides['canonical'] ?? url()->current(),
            'image' => $overrides['image'] ?? asset('imgs/og-image.jpg'),
            'site_name' => $site['org_name'],
            'type' => $overrides['type'] ?? 'website',
            'robots' => $overrides['robots'] ?? 'index, follow',
        ];
    }
}
