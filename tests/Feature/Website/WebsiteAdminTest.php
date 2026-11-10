<?php

use App\Models\Consent;
use App\Models\ContactInfo;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\Student;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Permission::findOrCreate('manage website content');
    $role = Role::findOrCreate('Editor');
    $role->givePermissionTo('manage website content');

    $this->editor = User::factory()->create();
    $this->editor->assignRole($role);

    $this->outsider = User::factory()->create();
});

it('blocks users without permission from the website admin', function () {
    actingAs($this->outsider)->get(route('admin.website.index'))->assertForbidden();
});

it('allows an editor to open the website admin', function () {
    actingAs($this->editor)->get(route('admin.website.index'))->assertOk();
});

it('renders the editor screens', function (string $routeName) {
    SiteSetting::query()->firstOrCreate([]);
    ContactInfo::query()->firstOrCreate([]);

    actingAs($this->editor)->get(route($routeName))->assertOk();
})->with([
    'admin.website.settings.edit',
    'admin.website.contact.edit',
    'admin.website.pages.index',
    'admin.website.news.index',
    'admin.website.news.create',
]);

it('updates site settings and clears the cache', function () {
    SiteSetting::create(['org_name' => 'Old name']);

    actingAs($this->editor)
        ->put(route('admin.website.settings.update'), [
            'org_name' => 'Youthline Malawi',
            'tagline' => 'New tagline',
            'social_links' => ['facebook' => 'https://facebook.com/yl'],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(SiteSetting::query()->value('org_name'))->toBe('Youthline Malawi');
});

it('updates a page', function () {
    $page = Page::create(['key' => 'about', 'title' => 'About', 'summary' => 'Old', 'body' => 'Old body']);

    actingAs($this->editor)
        ->put(route('admin.website.pages.update', $page), [
            'title' => 'About Youthline',
            'summary' => 'New summary',
            'body' => 'New body text.',
        ])
        ->assertRedirect(route('admin.website.pages.index'));

    expect($page->fresh()->title)->toBe('About Youthline')
        ->and($page->fresh()->summary)->toBe('New summary');
});

it('updates contact details', function () {
    ContactInfo::create(['email' => 'old@youthline.org']);

    actingAs($this->editor)
        ->put(route('admin.website.contact.update'), ['email' => 'new@youthline.org', 'phone' => '123'])
        ->assertRedirect();

    expect(ContactInfo::query()->value('email'))->toBe('new@youthline.org');
});

it('creates a draft news post', function () {
    actingAs($this->editor)
        ->post(route('admin.website.news.store'), [
            'title' => 'A new story',
            'category' => 'news',
            'body' => 'Body',
            'is_published' => false,
        ])
        ->assertRedirect(route('admin.website.news.index'));

    expect(NewsPost::where('title', 'A new story')->exists())->toBeTrue();
});

it('refuses to publish a student story without consent', function () {
    $student = Student::create(['first_name' => 'A', 'last_name' => 'B', 'status' => 'active']);
    Consent::create(['student_id' => $student->id, 'story_allowed' => false]);

    actingAs($this->editor)
        ->post(route('admin.website.news.store'), [
            'title' => 'Private story',
            'category' => 'success_story',
            'student_id' => $student->id,
            'is_published' => true,
        ])
        ->assertSessionHasErrors('is_published');

    expect(NewsPost::count())->toBe(0);
});

it('publishes a student story when consent is on file', function () {
    $student = Student::create(['first_name' => 'A', 'last_name' => 'B', 'status' => 'active']);
    Consent::create(['student_id' => $student->id, 'story_allowed' => true]);

    actingAs($this->editor)
        ->post(route('admin.website.news.store'), [
            'title' => 'Shared story',
            'category' => 'success_story',
            'student_id' => $student->id,
            'is_published' => true,
        ])
        ->assertRedirect(route('admin.website.news.index'));

    expect(NewsPost::where('title', 'Shared story')->where('is_published', true)->exists())->toBeTrue();
});

it('toggles publish state and blocks publishing a non-consented story', function () {
    $student = Student::create(['first_name' => 'A', 'last_name' => 'B', 'status' => 'active']);
    Consent::create(['student_id' => $student->id, 'story_allowed' => false]);
    $post = NewsPost::create(['title' => 'Draft', 'category' => 'success_story', 'student_id' => $student->id, 'is_published' => false]);

    actingAs($this->editor)
        ->put(route('admin.website.news.publish', $post))
        ->assertSessionHas('error');

    expect($post->fresh()->is_published)->toBeFalse();
});
