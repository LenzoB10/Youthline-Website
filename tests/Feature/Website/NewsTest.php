<?php

use App\Models\Consent;
use App\Models\NewsPost;
use App\Models\Student;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

function publishedPost(array $attributes = []): NewsPost
{
    return NewsPost::create(array_merge([
        'title' => 'A Youthline update',
        'category' => 'news',
        'body' => 'Something good happened.',
        'is_published' => true,
        'published_at' => now(),
    ], $attributes));
}

it('lists only published posts on the news page', function () {
    publishedPost(['title' => 'Live post']);
    NewsPost::create(['title' => 'Draft post', 'category' => 'news', 'is_published' => false]);

    get(route('news'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/News')
            ->has('posts', 1)
            ->where('posts.0.title', 'Live post')
        );
});

it('hides student stories without consent but shows them with consent', function () {
    $noConsent = Student::create(['first_name' => 'No', 'last_name' => 'Consent', 'status' => 'active']);
    Consent::create(['student_id' => $noConsent->id, 'story_allowed' => false]);
    publishedPost(['title' => 'Hidden story', 'category' => 'success_story', 'student_id' => $noConsent->id]);

    $consented = Student::create(['first_name' => 'Has', 'last_name' => 'Consent', 'status' => 'active']);
    Consent::create(['student_id' => $consented->id, 'story_allowed' => true]);
    publishedPost(['title' => 'Shared story', 'category' => 'success_story', 'student_id' => $consented->id]);

    get(route('news'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('posts', 1)
            ->where('posts.0.title', 'Shared story')
        );
});

it('shows a published post detail page', function () {
    $post = publishedPost(['title' => 'Readable story']);

    get(route('news.show', $post))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/NewsShow')
            ->where('post.title', 'Readable story')
        );
});

it('returns 404 for a draft post detail', function () {
    $draft = NewsPost::create(['title' => 'Draft', 'category' => 'news', 'is_published' => false]);

    get(route('news.show', $draft))->assertNotFound();
});

it('returns 404 for a student story without consent', function () {
    $student = Student::create(['first_name' => 'No', 'last_name' => 'Consent', 'status' => 'active']);
    Consent::create(['student_id' => $student->id, 'story_allowed' => false]);
    $post = publishedPost(['title' => 'Private', 'category' => 'success_story', 'student_id' => $student->id]);

    get(route('news.show', $post))->assertNotFound();
});

it('passes the latest news to the home page', function () {
    publishedPost(['title' => 'Homepage news']);

    get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('news', 1));
});

it('filters the news page by category and counts each category', function () {
    publishedPost(['title' => 'Plain news']);
    publishedPost(['title' => 'Big win', 'category' => 'success_story']);
    publishedPost(['title' => 'Uniform day', 'category' => 'activity']);
    NewsPost::create(['title' => 'Hidden draft', 'category' => 'activity', 'is_published' => false]);

    get(route('news', ['category' => 'success_story']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('posts', 1)
            ->where('posts.0.title', 'Big win')
            ->where('activeCategory', 'success_story')
            ->where('totalPosts', 3)
            ->where('categories.2.key', 'activity')
            ->where('categories.2.count', 1)
        );

    // Unknown categories fall back to everything.
    get(route('news', ['category' => 'nonsense']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('posts', 3)->where('activeCategory', null));
});

it('leads with the newest post and gives each post a reading time', function () {
    publishedPost(['title' => 'Older', 'published_at' => now()->subWeek()]);
    publishedPost(['title' => 'Newest', 'body' => str_repeat('word ', 450), 'published_at' => now()]);

    get(route('news'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('posts.0.title', 'Newest')
            ->where('posts.0.reading_minutes', 3)
            ->where('posts.1.reading_minutes', 1)
        );
});
