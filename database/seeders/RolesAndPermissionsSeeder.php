<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::updateOrCreate(['name' => 'Admin']);

        // User Management Permissions
        Permission::updateOrCreate(['name' => 'add user']);
        Permission::updateOrCreate(['name' => 'see user details']);
        Permission::updateOrCreate(['name' => 'list users']);
        Permission::updateOrCreate(['name' => 'update user']);
        Permission::updateOrCreate(['name' => 'delete user']);
        Permission::updateOrCreate(['name' => 'suspend user']);

        // Role Management Permissions
        Permission::updateOrCreate(['name' => 'add role']);
        Permission::updateOrCreate(['name' => 'list roles']);
        Permission::updateOrCreate(['name' => 'see role details']);
        Permission::updateOrCreate(['name' => 'update role']);
        Permission::updateOrCreate(['name' => 'delete role']);

        Permission::updateOrCreate(['name' => 'add user permissions']);
        Permission::updateOrCreate(['name' => 'update user role']);

        // System Permissions
        Permission::updateOrCreate(['name' => 'update settings']);

        // Public website content management
        Permission::updateOrCreate(['name' => 'manage website content']);

        // Audit Log Permissions
        Permission::updateOrCreate(['name' => 'list audit logs']);
        Permission::updateOrCreate(['name' => 'see audit log details']);
        Permission::updateOrCreate(['name' => 'see user audit logs']);
        Permission::updateOrCreate(['name' => 'see audit logs']); // For department logs

        // Database Backup Permissions
        Permission::updateOrCreate(['name' => 'list backups']);
        Permission::updateOrCreate(['name' => 'create backup']);
        Permission::updateOrCreate(['name' => 'download backup']);
        Permission::updateOrCreate(['name' => 'delete backup']);
        Permission::updateOrCreate(['name' => 'update backup settings']);

        // Assign all permissions to Admin
        $admin->givePermissionTo(Permission::all());

        $user = User::where('email', 'promisemphoola2@gmail.com')->first();
        if ($user) {
            $user->assignRole('Admin');
        }
    }
}
