<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

test('there is no public sign-up page', function () {
    $this->get('/register')->assertNotFound();

    $this->post('/register', [
        'name' => 'Stranger',
        'email' => 'stranger@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
    expect(User::query()->where('email', 'stranger@example.com')->exists())->toBeFalse();
});

test('the login page hides the Google button until Google sign-in is configured', function () {
    config(['services.google.client_id' => null]);
    $this->get('/login')->assertInertia(fn ($page) => $page->where('googleLogin', false));

    config(['services.google.client_id' => 'configured-id']);
    $this->get('/login')->assertInertia(fn ($page) => $page->where('googleLogin', true));
});

test('Google sign-in only works for existing accounts and never creates one', function () {
    $fakeGoogleUser = function (string $email) {
        $user = Mockery::mock(Laravel\Socialite\Two\User::class);
        $user->shouldReceive('getEmail')->andReturn($email);
        $user->shouldReceive('getId')->andReturn('google-123');
        $user->shouldReceive('getName')->andReturn('Someone');
        Socialite::shouldReceive('driver->user')->once()->andReturn($user);
    };

    // A stranger is turned away and no account is made.
    $fakeGoogleUser('stranger@example.com');
    $this->get(route('auth.google.callback'))->assertRedirect(route('login'));
    $this->assertGuest();
    expect(User::query()->where('email', 'stranger@example.com')->exists())->toBeFalse();

    // Someone the Admin already added can sign in.
    $member = User::factory()->create(['email' => 'member@example.com', 'status' => 'active']);
    $fakeGoogleUser('member@example.com');
    $this->get(route('auth.google.callback'));
    $this->assertAuthenticatedAs($member);
});
