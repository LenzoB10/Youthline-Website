<?php

use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

it('renders each public page with its SEO payload', function (string $routeName, string $component) {
    get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component($component)
            ->has('seo.title')
            ->has('seo.description')
            ->has('seo.canonical')
        );
})->with([
    'home' => ['home', 'Public/Home'],
    'about' => ['about', 'Public/About'],
    'programs' => ['programs', 'Public/Programs'],
    'get involved' => ['get-involved', 'Public/GetInvolved'],
    'news' => ['news', 'Public/News'],
    'contact' => ['contact', 'Public/Contact'],
]);

it('shares branding on public pages', function () {
    get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('site.org_name')
            ->has('site.tagline')
            ->has('site.contact')
        );
});

it('does not require authentication for the public site', function () {
    get(route('home'))->assertOk();
    get(route('contact'))->assertOk();
});
