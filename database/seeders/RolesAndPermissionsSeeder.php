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

        // Students (System Guide permissions table): managers view/add/edit,
        // change status and promote, and handle documents & consent.
        $managerStudentPermissions = [
            'list students',
            'add student',
            'update student',
            'change student status',
            'manage student documents',
        ];
        foreach ($managerStudentPermissions as $permission) {
            Permission::updateOrCreate(['name' => $permission]);
        }
        // Only the Admin may permanently delete a student or a student document.
        Permission::updateOrCreate(['name' => 'delete student']);
        Permission::updateOrCreate(['name' => 'delete student documents']);

        // Academics & fees (Phase 3): managers record results, fees and payments and
        // can mark a payment as an error — never delete it.
        $managerAcademicPermissions = ['record results', 'manage fees'];
        foreach ($managerAcademicPermissions as $permission) {
            Permission::updateOrCreate(['name' => $permission]);
        }
        // Admin only: sign off over-limit payments and new scholarships; delete results.
        Permission::updateOrCreate(['name' => 'approve payments']);
        Permission::updateOrCreate(['name' => 'delete results']);

        // Support programs (Phase 4): managers view and edit every module.
        $managerSupportPermissions = [
            'manage basic needs',
            'manage mentorship',
            'manage home visits',
            'manage businesses',
            'manage careers',
        ];
        foreach ($managerSupportPermissions as $permission) {
            Permission::updateOrCreate(['name' => $permission]);
        }
        // Admin only: delete support records, and read every
        // session note (managers see notes per the Settings visibility rule).
        Permission::updateOrCreate(['name' => 'delete support records']);
        Permission::updateOrCreate(['name' => 'view all session notes']);

        // Sponsors & reporting (Phase 5): managers keep sponsors, write progress
        // updates and export programme reports; the Admin also deletes sponsors and
        // sees the organisation-wide overview.
        $managerReportingPermissions = ['manage sponsors', 'export reports'];
        foreach ($managerReportingPermissions as $permission) {
            Permission::updateOrCreate(['name' => $permission]);
        }
        Permission::updateOrCreate(['name' => 'delete sponsors']);
        Permission::updateOrCreate(['name' => 'view organisation overview']);

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

        // Manager runs the programs day to day (and edits website content).
        Role::updateOrCreate(['name' => 'Manager'])
            ->givePermissionTo([...$managerStudentPermissions, ...$managerAcademicPermissions, ...$managerSupportPermissions, ...$managerReportingPermissions, 'manage website content']);

        $user = User::where('email', 'lenabekelesi@gmail.com')->first();
        if ($user) {
            $user->assignRole('Admin');
        }
    }
}
