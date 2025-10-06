<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('NotificationsController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    describe('index method', function () {
        it('displays notifications index page', function () {
            $response = $this->get('/notifications');

            $response->assertStatus(200);
            $response->assertInertia(fn (Assert $page) => 
                $page->component('Notifications/Index')
                    ->has('notifications')
            );
        });

        it('displays user notifications with correct structure', function () {
            // Create test notifications
            DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440000',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => [
                    'title' => 'Test Notification',
                    'message' => 'This is a test message'
                ],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->get('/notifications');

            $response->assertInertia(fn (Assert $page) => 
                $page->has('notifications')
                    ->where('notifications.0.title', 'Test Notification')
                    ->where('notifications.0.message', 'This is a test message')
                    ->where('notifications.0.read', false)
                    ->has('notifications.0.id')
                    ->has('notifications.0.created_at')
            );
        });

        it('handles notifications without title gracefully', function () {
            DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440001',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => [
                    'message' => 'Message without title'
                ],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->get('/notifications');

            $response->assertInertia(fn (Assert $page) => 
                $page->where('notifications.0.title', 'Notification')
                    ->where('notifications.0.message', 'Message without title')
            );
        });

        it('handles notifications without message gracefully', function () {
            DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440002',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => [
                    'title' => 'Title without message'
                ],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->get('/notifications');

            $response->assertInertia(fn (Assert $page) => 
                $page->where('notifications.0.title', 'Title without message')
                    ->where('notifications.0.message', '')
            );
        });

        it('shows read status correctly for read notifications', function () {
            DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440003',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => [
                    'title' => 'Read Notification',
                    'message' => 'This notification was read'
                ],
                'read_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->get('/notifications');

            $response->assertInertia(fn (Assert $page) => 
                $page->where('notifications.0.read', true)
            );
        });

        it('paginates notifications correctly', function () {
            // Create 25 notifications to test pagination
            for ($i = 0; $i < 25; $i++) {
                DatabaseNotification::create([
                    'id' => '550e8400-e29b-41d4-a716-' . str_pad($i, 12, '0', STR_PAD_LEFT),
                    'type' => 'App\Notifications\TestNotification',
                    'notifiable_type' => User::class,
                    'notifiable_id' => $this->user->id,
                    'data' => [
                        'title' => "Notification {$i}",
                        'message' => "Message {$i}"
                    ],
                    'read_at' => null,
                    'created_at' => now()->subMinutes($i),
                    'updated_at' => now()->subMinutes($i),
                ]);
            }

            $response = $this->get('/notifications');

            // The controller maps over paginated data, so we get 20 items
            $response->assertInertia(fn (Assert $page) => 
                $page->has('notifications', 20)
            );
        });

        it('orders notifications by latest first', function () {
            $olderNotification = DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440004',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => [
                    'title' => 'Older Notification',
                    'message' => 'This is older'
                ],
                'read_at' => null,
                'created_at' => now()->subHour(),
                'updated_at' => now()->subHour(),
            ]);

            $newerNotification = DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440005',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => [
                    'title' => 'Newer Notification',
                    'message' => 'This is newer'
                ],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->get('/notifications');

            $response->assertInertia(fn (Assert $page) => 
                $page->where('notifications.0.title', 'Newer Notification')
                    ->where('notifications.1.title', 'Older Notification')
            );
        });

        it('only shows notifications for the authenticated user', function () {
            $otherUser = User::factory()->create();
            
            // Create notification for other user
            DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440006',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $otherUser->id,
                'data' => [
                    'title' => 'Other User Notification',
                    'message' => 'This should not appear'
                ],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create notification for current user
            DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440007',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => [
                    'title' => 'My Notification',
                    'message' => 'This should appear'
                ],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->get('/notifications');

            $response->assertInertia(fn (Assert $page) => 
                $page->has('notifications', 1)
                    ->where('notifications.0.title', 'My Notification')
            );
        });
    });

    describe('markAsRead method', function () {
        it('marks a notification as read', function () {
            $notification = DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440008',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => [
                    'title' => 'Test Notification',
                    'message' => 'Test message'
                ],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->post("/notifications/{$notification->id}/mark-as-read");

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Notification marked as read.');
            
            // Verify notification is marked as read
            $notification->refresh();
            expect($notification->read_at)->not->toBeNull();
        });

        it('returns 404 for non-existent notification', function () {
            $response = $this->post('/notifications/non-existent-id/mark-as-read');

            $response->assertStatus(404);
        });

        it('returns 404 for notification belonging to other user', function () {
            $otherUser = User::factory()->create();
            
            $notification = DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440009',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $otherUser->id,
                'data' => [
                    'title' => 'Other User Notification',
                    'message' => 'Test message'
                ],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->post("/notifications/{$notification->id}/mark-as-read");

            $response->assertStatus(404);
        });
    });

    describe('markManyAsRead method', function () {
        it('marks multiple notifications as read', function () {
            $notification1 = DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440010',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => ['title' => 'Test 1', 'message' => 'Message 1'],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $notification2 = DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440011',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => ['title' => 'Test 2', 'message' => 'Message 2'],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->post('/notifications/mark-many-as-read', [
                'ids' => [$notification1->id, $notification2->id]
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Selected notifications marked as read.');
            
            // Verify both notifications are marked as read
            $notification1->refresh();
            $notification2->refresh();
            expect($notification1->read_at)->not->toBeNull();
            expect($notification2->read_at)->not->toBeNull();
        });

        it('handles empty ids array gracefully', function () {
            $response = $this->post('/notifications/mark-many-as-read', [
                'ids' => []
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Selected notifications marked as read.');
        });

        it('ignores non-existent notification ids', function () {
            $notification = DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440012',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => ['title' => 'Test', 'message' => 'Message'],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->post('/notifications/mark-many-as-read', [
                'ids' => [$notification->id, 'non-existent-id']
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Selected notifications marked as read.');
            
            // Verify existing notification is marked as read
            $notification->refresh();
            expect($notification->read_at)->not->toBeNull();
        });

        it('only marks notifications belonging to authenticated user', function () {
            $otherUser = User::factory()->create();
            
            $myNotification = DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440013',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => ['title' => 'My Notification', 'message' => 'Message'],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $otherNotification = DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440014',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $otherUser->id,
                'data' => ['title' => 'Other Notification', 'message' => 'Message'],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->post('/notifications/mark-many-as-read', [
                'ids' => [$myNotification->id, $otherNotification->id]
            ]);

            $response->assertRedirect();
            
            // Verify only my notification is marked as read
            $myNotification->refresh();
            $otherNotification->refresh();
            expect($myNotification->read_at)->not->toBeNull();
            expect($otherNotification->read_at)->toBeNull();
        });
    });

    describe('destroy method', function () {
        it('deletes a notification', function () {
            $notification = DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440015',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => ['title' => 'Test', 'message' => 'Message'],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->delete("/notifications/{$notification->id}");

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Notification deleted successfully.');
            
            // Verify notification is deleted
            expect(DatabaseNotification::find($notification->id))->toBeNull();
        });

        it('returns 404 for non-existent notification', function () {
            $response = $this->delete('/notifications/non-existent-id');

            $response->assertStatus(404);
        });

        it('returns 404 for notification belonging to other user', function () {
            $otherUser = User::factory()->create();
            
            $notification = DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440016',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $otherUser->id,
                'data' => ['title' => 'Other', 'message' => 'Message'],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->delete("/notifications/{$notification->id}");

            $response->assertStatus(404);
            
            // Verify notification still exists
            expect(DatabaseNotification::find($notification->id))->not->toBeNull();
        });
    });

    describe('destroyAll method', function () {
        it('deletes all notifications for authenticated user', function () {
            $notification1 = DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440017',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => ['title' => 'Test 1', 'message' => 'Message 1'],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $notification2 = DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440018',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => ['title' => 'Test 2', 'message' => 'Message 2'],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->delete('/notifications');

            $response->assertRedirect();
            $response->assertSessionHas('success', 'All notifications deleted successfully.');
            
            // Verify all user's notifications are deleted
            expect($this->user->notifications()->count())->toBe(0);
        });

        it('only deletes notifications for authenticated user', function () {
            $otherUser = User::factory()->create();
            
            $myNotification = DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440019',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user->id,
                'data' => ['title' => 'My Notification', 'message' => 'Message'],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $otherNotification = DatabaseNotification::create([
                'id' => '550e8400-e29b-41d4-a716-446655440020',
                'type' => 'App\Notifications\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $otherUser->id,
                'data' => ['title' => 'Other Notification', 'message' => 'Message'],
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->delete('/notifications');

            $response->assertRedirect();
            
            // Verify only my notifications are deleted
            expect($this->user->notifications()->count())->toBe(0);
            expect($otherUser->notifications()->count())->toBe(1);
        });

        it('handles case with no notifications gracefully', function () {
            $response = $this->delete('/notifications');

            $response->assertRedirect();
            $response->assertSessionHas('success', 'All notifications deleted successfully.');
        });
    });
});
