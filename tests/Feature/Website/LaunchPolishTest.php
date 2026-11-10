<?php

use App\Models\NewsPost;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows visitors a branded, non-indexed public 404 page', function () {
    get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Error')
            ->where('status', 404)
            ->has('site.org_name')
            ->where('seo.robots', 'noindex, nofollow')
            ->where('seo.title', 'Page not found'));
});

it('uses the public error page for a missing news post even when signed in', function () {
    $post = NewsPost::create(['title' => 'Draft', 'is_published' => false]);

    actingAs(User::factory()->create())
        ->get(route('news.show', $post))
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Error'));
});

it('keeps the admin error page for signed-in staff on admin screens', function () {
    Permission::findOrCreate('manage website content');
    $role = Role::findOrCreate('Editor');
    $role->givePermissionTo('manage website content');
    $editor = User::factory()->create();
    $editor->assignRole($role);

    actingAs($editor)
        ->get('/website/news/999999/edit')
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Errors/Index')->where('status', 404));

    actingAs(User::factory()->create())
        ->get(route('admin.website.index'))
        ->assertForbidden()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Errors/Index')->where('status', 403));
});

it('serves favicons, the web manifest and the share image in the page head', function () {
    get(route('home'))
        ->assertOk()
        ->assertSee('favicon.ico', false)
        ->assertSee('apple-touch-icon.png', false)
        ->assertSee('site.webmanifest', false)
        ->assertSee('imgs/og-image.jpg', false);
});

it('ships every optimised asset the public pages reference', function (string $path) {
    expect(public_path($path))->toBeFile();
})->with([
    'favicon.ico',
    'favicon-32.png',
    'apple-touch-icon.png',
    'icon-512.png',
    'site.webmanifest',
    'imgs/og-image.jpg',
    'imgs/youthline-logo-sm.png',
    'imgs/web/lg/IMG20261028134004.jpg',
    'imgs/web/sm/IMG20261028134004.jpg',
    'imgs/web/md/IMG20261028134052.jpg',
]);

it('does not ship a static robots.txt that would hide the dynamic one', function () {
    expect(public_path('robots.txt'))->not->toBeFile();
});
