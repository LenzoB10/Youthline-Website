<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('RolesController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        
        // Create permissions
        $this->seeRoleDetailsPermission = Permission::create(['name' => 'see role details']);
        $this->addRolePermission = Permission::create(['name' => 'add role']);
        $this->updateRolePermission = Permission::create(['name' => 'update role']);
        $this->deleteRolePermission = Permission::create(['name' => 'delete role']);
        
        $this->user->givePermissionTo([
            $this->seeRoleDetailsPermission,
            $this->addRolePermission,
            $this->updateRolePermission,
            $this->deleteRolePermission
        ]);
        
        $this->actingAs($this->user);
    });

    describe('index method', function () {
        it('can display roles index page', function () {
            $permission1 = Permission::create(['name' => 'test permission 1']);
            $permission2 = Permission::create(['name' => 'test permission 2']);
            
            $role = Role::create(['name' => 'Test Role']);
            $role->givePermissionTo([$permission1, $permission2]);
            
            $userWithRole = User::factory()->create();
            $userWithRole->assignRole($role);

            $response = $this->get('/roles');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => 
                $page->component('Roles/Index')
                    ->has('roles')
                    ->has('permissions')
                    ->where('roles.0.name', 'Test Role')
                    ->where('roles.0.users_count', 1)
                    ->has('roles.0.permissions', 2)
            );
        });

        it('shows roles with user counts', function () {
            $role1 = Role::create(['name' => 'Role One']);
            $role2 = Role::create(['name' => 'Role Two']);
            
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $user3 = User::factory()->create();
            
            $user1->assignRole($role1);
            $user2->assignRole($role1);
            $user3->assignRole($role2);

            $response = $this->get('/roles');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => 
                $page->component('Roles/Index')
                    ->has('roles', 2)
                    ->where('roles.0.users_count', 2)
                    ->where('roles.1.users_count', 1)
            );
        });

        it('includes all permissions in response', function () {
            $response = $this->get('/roles');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => 
                $page->component('Roles/Index')
                    ->has('permissions')
                    ->has('permissions.0.id')
                    ->has('permissions.0.name')
            );
        });
    });

    describe('show method', function () {
        it('can display role details for authorized user', function () {
            $permission = Permission::create(['name' => 'test permission']);
            $role = Role::create(['name' => 'Test Role']);
            $role->givePermissionTo($permission);
            
            $userWithRole = User::factory()->create();
            $userWithRole->assignRole($role);

            $response = $this->get("/roles/{$role->id}");

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => 
                $page->component('Roles/Show')
                    ->has('role')
                    ->where('role.name', 'Test Role')
                    ->has('role.permissions', 1)
                    ->has('role.users', 1)
            );
        });

        it('denies access to unauthorized user', function () {
            $unauthorizedUser = User::factory()->create();
            $this->actingAs($unauthorizedUser);

            $role = Role::create(['name' => 'Test Role']);

            $response = $this->get("/roles/{$role->id}");

            $response->assertForbidden();
        });

        it('shows role with permissions and users loaded', function () {
            $permission1 = Permission::create(['name' => 'permission 1']);
            $permission2 = Permission::create(['name' => 'permission 2']);
            $role = Role::create(['name' => 'Test Role']);
            $role->givePermissionTo([$permission1, $permission2]);
            
            $user1 = User::factory()->create(['name' => 'User One']);
            $user2 = User::factory()->create(['name' => 'User Two']);
            $user1->assignRole($role);
            $user2->assignRole($role);

            $response = $this->get("/roles/{$role->id}");

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => 
                $page->component('Roles/Show')
                    ->where('role.name', 'Test Role')
                    ->has('role.permissions', 2)
                    ->has('role.users', 2)
            );
        });
    });

    describe('store method', function () {
        it('can create role with valid data', function () {
            $permission1 = Permission::create(['name' => 'permission 1']);
            $permission2 = Permission::create(['name' => 'permission 2']);

            $roleData = [
                'name' => 'New Role',
                'permissions' => [$permission1->name, $permission2->name]
            ];

            $response = $this->post('/roles', $roleData);

            $response->assertRedirect(route('admin.roles.index'));
            $response->assertSessionHas('success', 'Role created successfully');
            
            $this->assertDatabaseHas('roles', [
                'name' => 'New Role'
            ]);
            
            $role = Role::where('name', 'New Role')->first();
            expect($role->hasPermissionTo($permission1))->toBeTrue();
            expect($role->hasPermissionTo($permission2))->toBeTrue();
        });

        it('validates required fields', function () {
            $response = $this->post('/roles', []);

            $response->assertSessionHasErrors([
                'name',
                'permissions'
            ]);
        });

        it('validates unique role name', function () {
            Role::create(['name' => 'Existing Role']);

            $permission = Permission::create(['name' => 'test permission']);

            $response = $this->post('/roles', [
                'name' => 'Existing Role',
                'permissions' => [$permission->name]
            ]);

            $response->assertSessionHasErrors('name');
        });

        it('validates permissions array has at least one permission', function () {
            $response = $this->post('/roles', [
                'name' => 'Test Role',
                'permissions' => []
            ]);

            $response->assertSessionHasErrors('permissions');
        });

        it('denies access to unauthorized user', function () {
            $unauthorizedUser = User::factory()->create();
            $this->actingAs($unauthorizedUser);

            $permission = Permission::create(['name' => 'test permission']);

            $response = $this->post('/roles', [
                'name' => 'Test Role',
                'permissions' => [$permission->name]
            ]);

            $response->assertForbidden();
        });

        it('logs activity when role is created', function () {
            $permission = Permission::create(['name' => 'test permission']);

            $this->post('/roles', [
                'name' => 'Logged Role',
                'permissions' => [$permission->name]
            ]);

            $this->assertDatabaseHas('activity_log', [
                'event' => 'created',
                'log_name' => 'default',
                'description' => 'Role created'
            ]);
        });
    });

    describe('update method', function () {
        it('can update regular role with valid data', function () {
            $permission1 = Permission::create(['name' => 'permission 1']);
            $permission2 = Permission::create(['name' => 'permission 2']);
            $permission3 = Permission::create(['name' => 'permission 3']);
            
            $role = Role::create(['name' => 'Regular Role']);
            $role->givePermissionTo($permission1);

            $updateData = [
                'name' => 'Updated Role',
                'permissions' => [$permission2->name, $permission3->name]
            ];

            $response = $this->put("/roles/{$role->id}", $updateData);

            $response->assertRedirect(route('admin.roles.index'));
            $response->assertSessionHas('success', 'Role updated successfully');
            
            $role->refresh();
            expect($role->name)->toBe('Updated Role');
            expect($role->hasPermissionTo($permission2))->toBeTrue();
            expect($role->hasPermissionTo($permission3))->toBeTrue();
            expect($role->hasPermissionTo($permission1))->toBeFalse();
        });

        it('can only update permissions for Admin role', function () {
            $permission1 = Permission::create(['name' => 'permission 1']);
            $permission2 = Permission::create(['name' => 'permission 2']);
            
            $role = Role::create(['name' => 'Admin']);
            $role->givePermissionTo($permission1);

            $updateData = [
                'name' => 'Should Not Change',
                'permissions' => [$permission2->name]
            ];

            $response = $this->put("/roles/{$role->id}", $updateData);

            $response->assertRedirect(route('admin.roles.index'));
            $response->assertSessionHas('success', 'Permissions updated successfully');
            
            $role->refresh();
            expect($role->name)->toBe('Admin'); // Name should not change
            expect($role->hasPermissionTo($permission2))->toBeTrue();
            expect($role->hasPermissionTo($permission1))->toBeFalse();
        });

        it('can only update permissions for Registrar role', function () {
            $permission1 = Permission::create(['name' => 'permission 1']);
            $permission2 = Permission::create(['name' => 'permission 2']);
            
            $role = Role::create(['name' => 'Registrar']);
            $role->givePermissionTo($permission1);

            $updateData = [
                'name' => 'Should Not Change',
                'permissions' => [$permission2->name]
            ];

            $response = $this->put("/roles/{$role->id}", $updateData);

            $response->assertRedirect(route('admin.roles.index'));
            $response->assertSessionHas('success', 'Permissions updated successfully');
            
            $role->refresh();
            expect($role->name)->toBe('Registrar'); // Name should not change
            expect($role->hasPermissionTo($permission2))->toBeTrue();
        });

        it('can only update permissions for Transport Officer role', function () {
            $permission1 = Permission::create(['name' => 'permission 1']);
            $permission2 = Permission::create(['name' => 'permission 2']);
            
            $role = Role::create(['name' => 'Transport Officer']);
            $role->givePermissionTo($permission1);

            $updateData = [
                'name' => 'Should Not Change',
                'permissions' => [$permission2->name]
            ];

            $response = $this->put("/roles/{$role->id}", $updateData);

            $response->assertRedirect(route('admin.roles.index'));
            $response->assertSessionHas('success', 'Permissions updated successfully');
            
            $role->refresh();
            expect($role->name)->toBe('Transport Officer'); // Name should not change
            expect($role->hasPermissionTo($permission2))->toBeTrue();
        });

        it('can only update permissions for HoD role', function () {
            $permission1 = Permission::create(['name' => 'permission 1']);
            $permission2 = Permission::create(['name' => 'permission 2']);
            
            $role = Role::create(['name' => 'HoD']);
            $role->givePermissionTo($permission1);

            $updateData = [
                'name' => 'Should Not Change',
                'permissions' => [$permission2->name]
            ];

            $response = $this->put("/roles/{$role->id}", $updateData);

            $response->assertRedirect(route('admin.roles.index'));
            $response->assertSessionHas('success', 'Permissions updated successfully');
            
            $role->refresh();
            expect($role->name)->toBe('HoD'); // Name should not change
            expect($role->hasPermissionTo($permission2))->toBeTrue();
        });

        it('validates update data same as creation', function () {
            $role = Role::create(['name' => 'Test Role']);

            $response = $this->put("/roles/{$role->id}", [
                'name' => '',
                'permissions' => []
            ]);

            $response->assertSessionHasErrors([
                'name',
                'permissions'
            ]);
        });

        it('validates unique name during update excluding current role', function () {
            $role1 = Role::create(['name' => 'Role One']);
            $role2 = Role::create(['name' => 'Role Two']);
            
            $permission = Permission::create(['name' => 'test permission']);

            $response = $this->put("/roles/{$role2->id}", [
                'name' => 'Role One', // Duplicate name
                'permissions' => [$permission->name]
            ]);

            $response->assertSessionHasErrors('name');
        });

        it('allows keeping same name during update', function () {
            $permission = Permission::create(['name' => 'test permission']);
            $role = Role::create(['name' => 'Test Role']);

            $response = $this->put("/roles/{$role->id}", [
                'name' => 'Test Role', // Same name
                'permissions' => [$permission->name]
            ]);

            $response->assertRedirect(route('admin.roles.index'));
            $response->assertSessionHas('success', 'Role updated successfully');
        });

        it('denies access to unauthorized user', function () {
            $unauthorizedUser = User::factory()->create();
            $this->actingAs($unauthorizedUser);

            $role = Role::create(['name' => 'Test Role']);
            $permission = Permission::create(['name' => 'test permission']);

            $response = $this->put("/roles/{$role->id}", [
                'name' => 'Updated Role',
                'permissions' => [$permission->name]
            ]);

            $response->assertForbidden();
        });

        it('logs activity when regular role is updated', function () {
            $permission = Permission::create(['name' => 'test permission']);
            $role = Role::create(['name' => 'Regular Role']);

            $this->put("/roles/{$role->id}", [
                'name' => 'Updated Role',
                'permissions' => [$permission->name]
            ]);

            $this->assertDatabaseHas('activity_log', [
                'event' => 'updated',
                'log_name' => 'default',
                'description' => 'Role updated'
            ]);
        });

        it('logs activity when protected role permissions are updated', function () {
            $permission = Permission::create(['name' => 'test permission']);
            $role = Role::create(['name' => 'Admin']);

            $this->put("/roles/{$role->id}", [
                'name' => 'Should Not Change',
                'permissions' => [$permission->name]
            ]);

            $this->assertDatabaseHas('activity_log', [
                'event' => 'updated',
                'log_name' => 'default',
                'description' => 'Updated permissions for protected role'
            ]);
        });
    });

    describe('destroy method', function () {
        it('can delete regular role without users', function () {
            $role = Role::create(['name' => 'Regular Role']);

            $response = $this->delete("/roles/{$role->id}");

            $response->assertRedirect(route('admin.roles.index'));
            $response->assertSessionHas('success', 'Role deleted successfully');
            
            $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        });

        it('prevents deletion of Admin role', function () {
            $role = Role::create(['name' => 'Admin']);

            $response = $this->delete("/roles/{$role->id}");

            $response->assertRedirect(route('admin.roles.index'));
            $response->assertSessionHas('error', 'You cannot delete the system role');
            
            $this->assertDatabaseHas('roles', ['id' => $role->id]);
        });

        it('prevents deletion of Registrar role', function () {
            $role = Role::create(['name' => 'Registrar']);

            $response = $this->delete("/roles/{$role->id}");

            $response->assertRedirect(route('admin.roles.index'));
            $response->assertSessionHas('error', 'You cannot delete the system role');
            
            $this->assertDatabaseHas('roles', ['id' => $role->id]);
        });

        it('prevents deletion of Transport Officer role', function () {
            $role = Role::create(['name' => 'Transport Officer']);

            $response = $this->delete("/roles/{$role->id}");

            $response->assertRedirect(route('admin.roles.index'));
            $response->assertSessionHas('error', 'You cannot delete the system role');
            
            $this->assertDatabaseHas('roles', ['id' => $role->id]);
        });

        it('prevents deletion of HoD role', function () {
            $role = Role::create(['name' => 'HoD']);

            $response = $this->delete("/roles/{$role->id}");

            $response->assertRedirect(route('admin.roles.index'));
            $response->assertSessionHas('error', 'You cannot delete the system role');
            
            $this->assertDatabaseHas('roles', ['id' => $role->id]);
        });

        it('prevents deletion when role has users', function () {
            $role = Role::create(['name' => 'Role With Users']);
            $userWithRole = User::factory()->create();
            $userWithRole->assignRole($role);

            $response = $this->delete("/roles/{$role->id}");

            $response->assertRedirect(route('admin.roles.index'));
            $response->assertSessionHas('error', 'You cannot delete a role with users');
            
            $this->assertDatabaseHas('roles', ['id' => $role->id]);
        });

        it('denies access to unauthorized user', function () {
            $unauthorizedUser = User::factory()->create();
            $this->actingAs($unauthorizedUser);

            $role = Role::create(['name' => 'Test Role']);

            $response = $this->delete("/roles/{$role->id}");

            $response->assertForbidden();
        });

        it('logs activity when role is deleted', function () {
            $role = Role::create(['name' => 'Deletable Role']);

            $this->delete("/roles/{$role->id}");

            $this->assertDatabaseHas('activity_log', [
                'event' => 'deleted',
                'log_name' => 'default',
                'description' => 'Deletable Role Role deleted'
            ]);
        });
    });

    describe('middleware', function () {
        it('applies correct middleware to each method', function () {
            $unauthorizedUser = User::factory()->create();
            $this->actingAs($unauthorizedUser);

            $role = Role::create(['name' => 'Test Role']);
            $permission = Permission::create(['name' => 'test permission']);

            // Test each route requires appropriate permission
            $responses = [
                $this->get("/roles/{$role->id}"),
                $this->post('/roles', []),
                $this->put("/roles/{$role->id}", []),
                $this->delete("/roles/{$role->id}")
            ];

            foreach ($responses as $response) {
                $response->assertForbidden();
            }
        });

        it('allows access when user has all required permissions', function () {
            $authorizedUser = User::factory()->create();
            $authorizedUser->givePermissionTo([
                $this->seeRoleDetailsPermission,
                $this->addRolePermission,
                $this->updateRolePermission,
                $this->deleteRolePermission
            ]);
            $this->actingAs($authorizedUser);

            $role = Role::create(['name' => 'Test Role']);

            $showResponse = $this->get("/roles/{$role->id}");
            $showResponse->assertOk();
        });

        it('does not require permission for index method', function () {
            $unauthorizedUser = User::factory()->create();
            $this->actingAs($unauthorizedUser);

            $response = $this->get('/roles');

            $response->assertOk(); // Should work without permissions due to commented middleware
        });
    });

    describe('edge cases and error handling', function () {
        it('handles invalid role ID gracefully', function () {
            $response = $this->get('/roles/999999');

            $response->assertNotFound();
        });

        it('handles role with no permissions', function () {
            $role = Role::create(['name' => 'Empty Role']);

            $response = $this->get("/roles/{$role->id}");

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => 
                $page->component('Roles/Show')
                    ->where('role.name', 'Empty Role')
                    ->has('role.permissions', 0)
            );
        });

        it('handles role with no users', function () {
            $role = Role::create(['name' => 'Unused Role']);

            $response = $this->get("/roles/{$role->id}");

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => 
                $page->component('Roles/Show')
                    ->where('role.name', 'Unused Role')
                    ->has('role.users', 0)
            );
        });

        it('handles very long role names during creation', function () {
            $permission = Permission::create(['name' => 'test permission']);
            $longName = str_repeat('Very Long Role Name ', 10); // 200+ characters

            $response = $this->post('/roles', [
                'name' => $longName,
                'permissions' => [$permission->name]
            ]);

            // Should work if within database field limits
            if (strlen($longName) <= 255) {
                $response->assertRedirect(route('admin.roles.index'));
                $response->assertSessionHas('success', 'Role created successfully');
            } else {
                $response->assertSessionHasErrors('name');
            }
        });

        it('handles special characters in role names', function () {
            $permission = Permission::create(['name' => 'test permission']);

            $response = $this->post('/roles', [
                'name' => 'Role-With_Special.Characters@123',
                'permissions' => [$permission->name]
            ]);

            $response->assertRedirect(route('admin.roles.index'));
            $response->assertSessionHas('success', 'Role created successfully');
        });

        it('handles large number of permissions', function () {
            $permissions = [];
            for ($i = 1; $i <= 50; $i++) {
                $permission = Permission::create(['name' => "permission {$i}"]);
                $permissions[] = $permission->name;
            }

            $response = $this->post('/roles', [
                'name' => 'Role With Many Permissions',
                'permissions' => $permissions
            ]);

            $response->assertRedirect(route('admin.roles.index'));
            $response->assertSessionHas('success', 'Role created successfully');
            
            $role = Role::where('name', 'Role With Many Permissions')->first();
            expect($role->permissions)->toHaveCount(50);
        });
    });
});
