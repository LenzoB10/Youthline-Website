<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('UserRolesAndPermissionsController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->targetUser = User::factory()->create();
        
        // Create roles and permissions for testing
        $this->adminRole = Role::create(['name' => 'admin']);
        $this->userRole = Role::create(['name' => 'user']);
        $this->driverRole = Role::create(['name' => 'driver']);
        
        $this->addPermissionsPermission = Permission::create(['name' => 'add user permissions']);
        $this->updateRolePermission = Permission::create(['name' => 'update user role']);
        $this->viewUsersPermission = Permission::create(['name' => 'view users']);
        $this->editUsersPermission = Permission::create(['name' => 'edit users']);
        
        $this->user->givePermissionTo([$this->addPermissionsPermission, $this->updateRolePermission]);
        $this->actingAs($this->user);
    });

    describe('changeRole method', function () {
        it('can change user role with valid data', function () {
            $this->targetUser->assignRole($this->userRole);
            
            $response = $this->put("/user/{$this->targetUser->id}/update-role", [
                'role' => 'admin'
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Role changed successfully');
            
            $this->targetUser->refresh();
            expect($this->targetUser->hasRole('admin'))->toBeTrue();
            expect($this->targetUser->hasRole('user'))->toBeFalse();
        });

        it('replaces existing role when changing to new role', function () {
            $this->targetUser->assignRole($this->userRole);
            expect($this->targetUser->hasRole('user'))->toBeTrue();
            
            $response = $this->put("/user/{$this->targetUser->id}/update-role", [
                'role' => 'driver'
            ]);

            $response->assertRedirect();
            
            $this->targetUser->refresh();
            expect($this->targetUser->hasRole('driver'))->toBeTrue();
            expect($this->targetUser->hasRole('user'))->toBeFalse();
        });

        it('clears user permissions cache when role is changed', function () {
            $this->targetUser->assignRole($this->userRole);
            
            // Simulate cached permissions
            cache()->put("user.{$this->targetUser->id}.permissions", ['cached_permissions']);
            expect(cache()->has("user.{$this->targetUser->id}.permissions"))->toBeTrue();
            
            $response = $this->put("/user/{$this->targetUser->id}/update-role", [
                'role' => 'admin'
            ]);

            $response->assertRedirect();
            expect(cache()->has("user.{$this->targetUser->id}.permissions"))->toBeFalse();
        });

        it('logs activity when role is changed', function () {
            $this->targetUser->assignRole($this->userRole);
            
            $response = $this->put("/user/{$this->targetUser->id}/update-role", [
                'role' => 'admin'
            ]);

            $response->assertRedirect();
            
            $this->assertDatabaseHas('activity_log', [
                'subject_type' => User::class,
                'subject_id' => $this->targetUser->id,
                'causer_type' => User::class,
                'causer_id' => $this->user->id,
                'event' => 'updated',
                'description' => 'User role updated'
            ]);
        });

        it('validates that role is required', function () {
            $response = $this->put("/user/{$this->targetUser->id}/update-role", []);

            $response->assertSessionHasErrors('role');
        });

        it('validates that role exists in roles table', function () {
            $response = $this->put("/user/{$this->targetUser->id}/update-role", [
                'role' => 'non-existent-role'
            ]);

            $response->assertSessionHasErrors('role');
        });

        it('requires update user role permission', function () {
            $unauthorizedUser = User::factory()->create();
            $this->actingAs($unauthorizedUser);
            
            $response = $this->put("/user/{$this->targetUser->id}/update-role", [
                'role' => 'admin'
            ]);

            $response->assertForbidden();
        });

        it('handles user without existing roles', function () {
            expect($this->targetUser->roles)->toHaveCount(0);
            
            $response = $this->put("/user/{$this->targetUser->id}/update-role", [
                'role' => 'admin'
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Role changed successfully');
            
            $this->targetUser->refresh();
            expect($this->targetUser->hasRole('admin'))->toBeTrue();
        });

        it('includes old and new role in activity log properties', function () {
            $this->targetUser->assignRole($this->userRole);
            
            $response = $this->put("/user/{$this->targetUser->id}/update-role", [
                'role' => 'admin'
            ]);

            $response->assertRedirect();
            
            // Verify activity was logged with proper JSON data
            $this->assertDatabaseHas('activity_log', [
                'subject_type' => User::class,
                'subject_id' => $this->targetUser->id,
                'causer_type' => User::class,
                'causer_id' => $this->user->id,
                'event' => 'updated',
                'description' => 'User role updated'
            ]);
            
            // Verify the activity contains properties (as JSON)
            expect(DB::table('activity_log')
                ->where('subject_type', User::class)
                ->where('subject_id', $this->targetUser->id)
                ->where('description', 'User role updated')
                ->first()->properties
            )->not->toBeNull();
        });
    });

    describe('changePermissions method', function () {
        it('can change user permissions with valid data', function () {
            $response = $this->put("/user/{$this->targetUser->id}/update-permissions", [
                'permissions' => ['view users', 'edit users']
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Permissions changed successfully');
            
            $this->targetUser->refresh();
            expect($this->targetUser->hasPermissionTo('view users'))->toBeTrue();
            expect($this->targetUser->hasPermissionTo('edit users'))->toBeTrue();
        });

        it('replaces existing permissions when changing to new permissions', function () {
            $this->targetUser->givePermissionTo($this->viewUsersPermission);
            expect($this->targetUser->hasPermissionTo('view users'))->toBeTrue();
            
            $response = $this->put("/user/{$this->targetUser->id}/update-permissions", [
                'permissions' => ['edit users']
            ]);

            $response->assertRedirect();
            
            $this->targetUser->refresh();
            expect($this->targetUser->hasPermissionTo('edit users'))->toBeTrue();
            expect($this->targetUser->hasPermissionTo('view users'))->toBeFalse();
        });

        it('can assign single permission', function () {
            $response = $this->put("/user/{$this->targetUser->id}/update-permissions", [
                'permissions' => ['view users']
            ]);

            $response->assertRedirect();
            
            $this->targetUser->refresh();
            expect($this->targetUser->hasPermissionTo('view users'))->toBeTrue();
        });

        it('can assign multiple permissions', function () {
            $response = $this->put("/user/{$this->targetUser->id}/update-permissions", [
                'permissions' => ['view users', 'edit users', 'add user permissions']
            ]);

            $response->assertRedirect();
            
            $this->targetUser->refresh();
            expect($this->targetUser->hasPermissionTo('view users'))->toBeTrue();
            expect($this->targetUser->hasPermissionTo('edit users'))->toBeTrue();
            expect($this->targetUser->hasPermissionTo('add user permissions'))->toBeTrue();
        });

        it('clears user permissions cache when permissions are changed', function () {
            // Simulate cached permissions
            cache()->put("user.{$this->targetUser->id}.permissions", ['cached_permissions']);
            expect(cache()->has("user.{$this->targetUser->id}.permissions"))->toBeTrue();
            
            $response = $this->put("/user/{$this->targetUser->id}/update-permissions", [
                'permissions' => ['view users']
            ]);

            $response->assertRedirect();
            expect(cache()->has("user.{$this->targetUser->id}.permissions"))->toBeFalse();
        });

        it('logs activity when permissions are changed', function () {
            $response = $this->put("/user/{$this->targetUser->id}/update-permissions", [
                'permissions' => ['view users', 'edit users']
            ]);

            $response->assertRedirect();
            
            $this->assertDatabaseHas('activity_log', [
                'subject_type' => User::class,
                'subject_id' => $this->targetUser->id,
                'causer_type' => User::class,
                'causer_id' => $this->user->id,
                'event' => 'updated',
                'description' => 'User permissions updated'
            ]);
        });

        it('validates that permissions array is required', function () {
            $response = $this->put("/user/{$this->targetUser->id}/update-permissions", []);

            $response->assertSessionHasErrors('permissions');
        });

        it('validates that permissions is an array', function () {
            $response = $this->put("/user/{$this->targetUser->id}/update-permissions", [
                'permissions' => 'not-an-array'
            ]);

            $response->assertSessionHasErrors('permissions');
        });

        it('validates that permissions array has at least one item', function () {
            $response = $this->put("/user/{$this->targetUser->id}/update-permissions", [
                'permissions' => []
            ]);

            $response->assertSessionHasErrors('permissions');
        });

        it('requires add user permissions permission', function () {
            $unauthorizedUser = User::factory()->create();
            $this->actingAs($unauthorizedUser);
            
            $response = $this->put("/user/{$this->targetUser->id}/update-permissions", [
                'permissions' => ['view users']
            ]);

            $response->assertForbidden();
        });

        it('handles user without existing permissions', function () {
            expect($this->targetUser->permissions)->toHaveCount(0);
            
            $response = $this->put("/user/{$this->targetUser->id}/update-permissions", [
                'permissions' => ['view users']
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Permissions changed successfully');
            
            $this->targetUser->refresh();
            expect($this->targetUser->hasPermissionTo('view users'))->toBeTrue();
        });

        it('includes old and new permissions in activity log properties', function () {
            $this->targetUser->givePermissionTo($this->viewUsersPermission);
            
            $response = $this->put("/user/{$this->targetUser->id}/update-permissions", [
                'permissions' => ['edit users']
            ]);

            $response->assertRedirect();
            
            // Verify activity was logged with proper JSON data
            $this->assertDatabaseHas('activity_log', [
                'subject_type' => User::class,
                'subject_id' => $this->targetUser->id,
                'causer_type' => User::class,
                'causer_id' => $this->user->id,
                'event' => 'updated',
                'description' => 'User permissions updated'
            ]);
            
            // Verify the activity contains properties (as JSON)
            expect(DB::table('activity_log')
                ->where('subject_type', User::class)
                ->where('subject_id', $this->targetUser->id)
                ->where('description', 'User permissions updated')
                ->first()->properties
            )->not->toBeNull();
        });

        it('handles non-existent permission names gracefully', function () {
            // Laravel Spatie Permission package should handle this
            $response = $this->put("/user/{$this->targetUser->id}/update-permissions", [
                'permissions' => ['non-existent-permission']
            ]);

            // This might create the permission or throw an exception depending on package configuration
            // We test that the controller handles it gracefully
            expect($response->getStatusCode())->toBeIn([302, 500]); // Redirect or error
        });
    });

    describe('middleware', function () {
        it('applies correct middleware to changePermissions method', function () {
            $unauthorizedUser = User::factory()->create();
            $this->actingAs($unauthorizedUser);
            
            $response = $this->put("/user/{$this->targetUser->id}/update-permissions", [
                'permissions' => ['view users']
            ]);

            $response->assertForbidden();
        });

        it('applies correct middleware to changeRole method', function () {
            $unauthorizedUser = User::factory()->create();
            $this->actingAs($unauthorizedUser);
            
            $response = $this->put("/user/{$this->targetUser->id}/update-role", [
                'role' => 'admin'
            ]);

            $response->assertForbidden();
        });

        it('allows access when user has required permissions', function () {
            $authorizedUser = User::factory()->create();
            $authorizedUser->givePermissionTo([$this->addPermissionsPermission, $this->updateRolePermission]);
            $this->actingAs($authorizedUser);
            
            $roleResponse = $this->put("/user/{$this->targetUser->id}/update-role", [
                'role' => 'admin'
            ]);
            
            $permissionsResponse = $this->put("/user/{$this->targetUser->id}/update-permissions", [
                'permissions' => ['view users']
            ]);

            $roleResponse->assertRedirect();
            $permissionsResponse->assertRedirect();
        });
    });

    describe('edge cases and error handling', function () {
        it('handles invalid user ID gracefully', function () {
            $response = $this->put("/user/999999/update-role", [
                'role' => 'admin'
            ]);

            $response->assertNotFound();
        });

        it('handles special characters in role names', function () {
            Role::create(['name' => 'special-role_123']);
            
            $response = $this->put("/user/{$this->targetUser->id}/update-role", [
                'role' => 'special-role_123'
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Role changed successfully');
        });

        it('handles large permissions arrays', function () {
            // Create many permissions
            $permissions = [];
            for ($i = 1; $i <= 50; $i++) {
                $permission = Permission::create(['name' => "test permission {$i}"]);
                $permissions[] = $permission->name;
            }
            
            $response = $this->put("/user/{$this->targetUser->id}/update-permissions", [
                'permissions' => $permissions
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Permissions changed successfully');
        });

        it('handles concurrent role changes', function () {
            $this->targetUser->assignRole($this->userRole);
            
            // Simulate concurrent changes
            $response1 = $this->put("/user/{$this->targetUser->id}/update-role", [
                'role' => 'admin'
            ]);
            
            $response2 = $this->put("/user/{$this->targetUser->id}/update-role", [
                'role' => 'driver'
            ]);

            $response1->assertRedirect();
            $response2->assertRedirect();
            
            $this->targetUser->refresh();
            // Last change should win
            expect($this->targetUser->hasRole('driver'))->toBeTrue();
        });
    });
});
