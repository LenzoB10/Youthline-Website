<?php

use App\Models\NewsPost;
use App\Models\SiteSetting;

use function Pest\Laravel\get;

beforeEach(function () {
    SiteSetting::create([
        'org_name' => 'Youthline',
        'tagline' => 'Empowering Young People. Transforming Lives. Building Futures.',
    ]);
});

it('renders per-page SEO meta tags in the document head', function () {
    $response = get(route('about'));

    $response->assertOk();
    $response->assertSee('<meta name="description"', false);
    $response->assertSee('<link rel="canonical"', false);
    $response->assertSee('property="og:title"', false);
    $response->assertSee('name="twitter:card"', false);
    $response->assertSee('About Us', false);
});

it('serves a valid XML sitemap including static pages', function () {
    $response = get('/sitemap.xml');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml');
    $response->assertSee('<urlset', false);
    $response->assertSee(route('home'), false);
    $response->assertSee(route('programs'), false);
});

it('includes published news posts in the sitemap', function () {
    $post = NewsPost::create([
        'title' => 'A milestone for Youthline',
        'category' => 'news',
        'is_published' => true,
        'published_at' => now(),
    ]);

    NewsPost::create([
        'title' => 'Draft not yet live',
        'category' => 'news',
        'is_published' => false,
    ]);

    $response = get('/sitemap.xml');

    $response->assertOk();
    $response->assertSee('#post-'.$post->id, false);
});

it('serves a robots.txt pointing to the sitemap', function () {
    $response = get('/robots.txt');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    $response->assertSee('User-agent: *', false);
    $response->assertSee('Sitemap: '.route('sitemap'), false);
});
