<?php

use App\Models\ContactMessage;
use App\Models\InvolvementRequest;
use App\Models\NewsletterSubscriber;

use function Pest\Laravel\post;

it('stores a contact message and flashes success', function () {
    post(route('contact.store'), [
        'name' => 'Jane Visitor',
        'email' => 'jane@example.com',
        'message' => 'I would like to help.',
    ])->assertRedirect()->assertSessionHas('success');

    expect(ContactMessage::where('email', 'jane@example.com')->where('status', 'new')->exists())->toBeTrue();
});

it('validates the contact form', function () {
    post(route('contact.store'), ['name' => '', 'email' => 'not-an-email', 'message' => ''])
        ->assertSessionHasErrors(['name', 'email', 'message']);

    expect(ContactMessage::count())->toBe(0);
});

it('silently drops honeypot contact spam', function () {
    post(route('contact.store'), [
        'name' => 'Bot',
        'email' => 'bot@example.com',
        'message' => 'spam',
        'company' => 'i-am-a-bot',
    ])->assertRedirect();

    expect(ContactMessage::count())->toBe(0);
});

it('stores an involvement request', function () {
    post(route('involvement.store'), [
        'name' => 'Sam Helper',
        'email' => 'sam@example.com',
        'type' => 'mentor',
    ])->assertRedirect()->assertSessionHas('success');

    expect(InvolvementRequest::where('email', 'sam@example.com')->where('type', 'mentor')->exists())->toBeTrue();
});

it('rejects an invalid involvement type', function () {
    post(route('involvement.store'), ['name' => 'Sam', 'email' => 'sam@example.com', 'type' => 'intruder'])
        ->assertSessionHasErrors('type');

    expect(InvolvementRequest::count())->toBe(0);
});

it('subscribes to the newsletter', function () {
    post(route('newsletter.store'), ['email' => 'subscriber@example.com'])
        ->assertRedirect()->assertSessionHas('success');

    expect(NewsletterSubscriber::where('email', 'subscriber@example.com')->exists())->toBeTrue();
});

it('does not duplicate newsletter subscriptions', function () {
    NewsletterSubscriber::create(['email' => 'dup@example.com']);

    post(route('newsletter.store'), ['email' => 'dup@example.com'])->assertRedirect();

    expect(NewsletterSubscriber::where('email', 'dup@example.com')->count())->toBe(1);
});
