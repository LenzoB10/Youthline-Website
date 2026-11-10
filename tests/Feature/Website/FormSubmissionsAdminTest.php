<?php

use App\Models\ContactMessage;
use App\Models\InvolvementRequest;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
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

it('blocks users without permission from submission screens', function (string $routeName) {
    actingAs($this->outsider)->get(route($routeName))->assertForbidden();
})->with([
    'admin.website.messages.index',
    'admin.website.involvement.index',
    'admin.website.subscribers.index',
    'admin.website.subscribers.export',
]);

it('lists contact messages newest first', function () {
    ContactMessage::create(['name' => 'Old', 'message' => 'Hi', 'status' => 'new', 'created_at' => now()->subDay()]);
    ContactMessage::create(['name' => 'New', 'message' => 'Hello', 'status' => 'new']);

    actingAs($this->editor)
        ->get(route('admin.website.messages.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Website/Messages/Index')
            ->has('messages', 2)
            ->where('messages.0.name', 'New'));
});

it('updates a contact message status', function () {
    $message = ContactMessage::create(['name' => 'Ada', 'status' => 'new']);

    actingAs($this->editor)
        ->put(route('admin.website.messages.update', $message), ['status' => 'replied'])
        ->assertRedirect();

    expect($message->fresh()->status)->toBe('replied');
});

it('rejects an invalid contact message status', function () {
    $message = ContactMessage::create(['name' => 'Ada', 'status' => 'new']);

    actingAs($this->editor)
        ->put(route('admin.website.messages.update', $message), ['status' => 'archived'])
        ->assertSessionHasErrors('status');

    expect($message->fresh()->status)->toBe('new');
});

it('deletes a contact message', function () {
    $message = ContactMessage::create(['name' => 'Ada', 'status' => 'new']);

    actingAs($this->editor)->delete(route('admin.website.messages.destroy', $message))->assertRedirect();

    expect(ContactMessage::query()->count())->toBe(0);
});

it('lists involvement requests', function () {
    InvolvementRequest::create(['name' => 'Mentor Mike', 'type' => 'mentor', 'status' => 'new']);

    actingAs($this->editor)
        ->get(route('admin.website.involvement.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Website/Involvement/Index')
            ->has('requests', 1)
            ->where('requests.0.type', 'mentor'));
});

it('updates and validates an involvement request status', function () {
    $request = InvolvementRequest::create(['name' => 'Vee', 'type' => 'volunteer', 'status' => 'new']);

    actingAs($this->editor)
        ->put(route('admin.website.involvement.update', $request), ['status' => 'accepted'])
        ->assertRedirect();
    expect($request->fresh()->status)->toBe('accepted');

    actingAs($this->editor)
        ->put(route('admin.website.involvement.update', $request), ['status' => 'replied'])
        ->assertSessionHasErrors('status');
    expect($request->fresh()->status)->toBe('accepted');
});

it('deletes an involvement request', function () {
    $request = InvolvementRequest::create(['name' => 'Vee', 'type' => 'volunteer', 'status' => 'new']);

    actingAs($this->editor)->delete(route('admin.website.involvement.destroy', $request))->assertRedirect();

    expect(InvolvementRequest::query()->count())->toBe(0);
});

it('lists and removes newsletter subscribers', function () {
    $subscriber = NewsletterSubscriber::create(['email' => 'a@example.com', 'subscribed_at' => now()]);

    actingAs($this->editor)
        ->get(route('admin.website.subscribers.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Website/Subscribers/Index')
            ->has('subscribers', 1));

    actingAs($this->editor)->delete(route('admin.website.subscribers.destroy', $subscriber))->assertRedirect();

    expect(NewsletterSubscriber::query()->count())->toBe(0);
});

it('exports newsletter subscribers as CSV', function () {
    NewsletterSubscriber::create(['email' => 'b@example.com', 'subscribed_at' => now()]);
    NewsletterSubscriber::create(['email' => 'a@example.com', 'subscribed_at' => now()]);

    $response = actingAs($this->editor)->get(route('admin.website.subscribers.export'));

    $response->assertOk()->assertDownload();
    expect($response->headers->get('Content-Type'))->toContain('text/csv');

    $lines = array_values(array_filter(explode("\n", $response->streamedContent())));
    expect($lines[0])->toContain('Email')
        ->and($lines[1])->toStartWith('a@example.com')
        ->and($lines[2])->toStartWith('b@example.com');
});

it('shows submission counts on the website dashboard', function () {
    ContactMessage::create(['name' => 'A', 'status' => 'new']);
    ContactMessage::create(['name' => 'B', 'status' => 'replied']);
    InvolvementRequest::create(['name' => 'C', 'type' => 'donor', 'status' => 'new']);
    NewsletterSubscriber::create(['email' => 'c@example.com']);

    actingAs($this->editor)
        ->get(route('admin.website.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.new_messages', 1)
            ->where('stats.new_requests', 1)
            ->where('stats.subscribers', 1));
});
