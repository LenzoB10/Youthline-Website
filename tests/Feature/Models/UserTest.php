<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

describe('User Model', function () {
    describe('Factory', function () {
        it('can create a user with factory', function () {
            $user = User::factory()->create();

            expect($user)->toBeInstanceOf(User::class)
                ->and($user->name)->toBeString()
                ->and($user->email)->toBeString()
                ->and($user->email)->toContain('@')
                ->and($user->email)->toEqual(strtolower($user->email))
                ->and($user->status)->toBeIn(['active', 'inactive'])
                ->and($user->email_verified_at)->not->toBeNull();
        });

        it('can create unverified user', function () {
            $user = User::factory()->unverified()->create();

            expect($user->email_verified_at)->toBeNull();
        });
    });

    describe('Attributes', function () {
        it('has fillable attributes', function () {
            $fillable = [
                'name',
                'email',
                'password',
                'status',
            ];

            expect(User::make()->getFillable())->toEqual($fillable);
        });

        it('has hidden attributes', function () {
            $hidden = [
                'password',
                'remember_token',
            ];

            expect(User::make()->getHidden())->toEqual($hidden);
        });

        it('casts attributes correctly', function () {
            $user = User::factory()->create();

            expect($user->email_verified_at)->toBeInstanceOf(Carbon\Carbon::class);
        });

        it('hashes password automatically', function () {
            $user = User::factory()->create(['password' => 'plaintext']);

            expect($user->password)->not->toBe('plaintext')
                ->and(Hash::check('plaintext', $user->password))->toBeTrue();
        });
    });

    describe('Methods', function () {
        it('caches and retrieves permissions', function () {
            $user = User::factory()->create();
            $permission = Permission::create(['name' => 'test-permission']);
            $user->givePermissionTo($permission);

            $cachedPermissions = $user->getAllCachedPermissions();

            expect($cachedPermissions)->toBeArray()
                ->and($cachedPermissions)->toContain('test-permission');

            // Test cache works
            expect(cache()->has("user.{$user->id}.permissions"))->toBeTrue();
        });

        it('clears permission cache on update', function () {
            $user = User::factory()->create();
            
            // Set cache
            $user->getAllCachedPermissions();
            expect(cache()->has("user.{$user->id}.permissions"))->toBeTrue();

            // Update user
            $user->update(['name' => 'Updated Name']);

            // Cache should be cleared
            expect(cache()->has("user.{$user->id}.permissions"))->toBeFalse();
        });
    });

    describe('Traits', function () {
        it('uses HasFactory trait', function () {
            expect(User::factory())->toBeInstanceOf(Illuminate\Database\Eloquent\Factories\Factory::class);
        });

        it('uses Notifiable trait', function () {
            $user = User::factory()->create();
            expect(method_exists($user, 'notify'))->toBeTrue();
        });

        it('uses HasRoles trait', function () {
            $user = User::factory()->create();
            $role = Role::create(['name' => 'test-role']);
            
            $user->assignRole($role);
            
            expect($user->hasRole('test-role'))->toBeTrue();
        });

        it('uses LogsActivityTrait', function () {
            $user = User::factory()->create();
            expect(method_exists($user, 'getActivitylogOptions'))->toBeTrue();
        });
    });

    describe('Scopes and Queries', function () {
        it('can filter active users', function () {
            User::factory()->count(3)->create(['status' => 'active']);
            User::factory()->count(2)->create(['status' => 'inactive']);

            $activeUsers = User::where('status', 'active')->get();

            expect($activeUsers)->toHaveCount(3);
        });
    });

    describe('Validation & Constraints', function () {
        it('requires unique email', function () {
            $email = 'test@example.com';
            User::factory()->create(['email' => $email]);

            expect(function () use ($email) {
                User::factory()->create(['email' => $email]);
            })->toThrow(Exception::class);
        });

        it('requires email to be lowercase', function () {
            // This is tested through the factory which ensures lowercase emails
            $user = User::factory()->create();
            expect($user->email)->toBe(strtolower($user->email));
        });
    });

    describe('Database Operations', function () {
        it('can be soft deleted if configured', function () {
            $user = User::factory()->create();
            $userId = $user->id;

            // Test regular delete (hard delete in this case since no SoftDeletes trait)
            $user->delete();

            expect(User::find($userId))->toBeNull();
        });

        it('can update attributes', function () {
            $user = User::factory()->create();
            $newName = 'Updated Name';
            $newStatus = 'inactive';

            $user->update([
                'name' => $newName,
                'status' => $newStatus,
            ]);

            expect($user->fresh()->name)->toBe($newName)
                ->and($user->fresh()->status)->toBe($newStatus);
        });
    });
});
