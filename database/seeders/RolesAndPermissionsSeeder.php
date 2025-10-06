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
        $hod = Role::updateOrCreate(['name' => 'HoD']);
        $financeDirector = Role::updateOrCreate(['name' => 'DoF']);
        $registrar = Role::updateOrCreate(['name' => 'Registrar']);
        $transportOfficer = Role::updateOrCreate(['name' => 'Transport Officer']);
        $driver = Role::updateOrCreate(['name' => 'Driver']);
        $staff = Role::updateOrCreate(['name' => 'Staff']);

        // User Management Permissions
        Permission::updateOrCreate(['name' => 'add user']);
        Permission::updateOrCreate(['name' => 'see user details']);
        Permission::updateOrCreate(['name' => 'list users']);
        Permission::updateOrCreate(['name' => 'update user']);
        Permission::updateOrCreate(['name' => 'delete user']);
        Permission::updateOrCreate(['name' => 'suspend user']);

        // Department Management Permissions
        Permission::updateOrCreate(['name' => 'add department']);
        Permission::updateOrCreate(['name' => 'see department details']);
        Permission::updateOrCreate(['name' => 'list departments']);
        Permission::updateOrCreate(['name' => 'update department']);
        Permission::updateOrCreate(['name' => 'delete department']);

        // Vehicle Management Permissions
        Permission::updateOrCreate(['name' => 'add vehicle']);
        Permission::updateOrCreate(['name' => 'see vehicle details']);
        Permission::updateOrCreate(['name' => 'list vehicles']);
        Permission::updateOrCreate(['name' => 'update vehicle']);
        Permission::updateOrCreate(['name' => 'delete vehicle']);

        // Driver Management Permissions
        Permission::updateOrCreate(['name' => 'add driver']);
        Permission::updateOrCreate(['name' => 'see driver details']);
        Permission::updateOrCreate(['name' => 'list drivers']);
        Permission::updateOrCreate(['name' => 'update driver']);
        Permission::updateOrCreate(['name' => 'delete driver']);

        // Vendor Management Permissions
        Permission::updateOrCreate(['name' => 'add vendor']);
        Permission::updateOrCreate(['name' => 'see vendor details']);
        Permission::updateOrCreate(['name' => 'list vendors']);
        Permission::updateOrCreate(['name' => 'update vendor']);
        Permission::updateOrCreate(['name' => 'delete vendor']);

        // Trip Management Permissions
        Permission::updateOrCreate(['name' => 'list trips']);
        Permission::updateOrCreate(['name' => 'see trip details']);
        Permission::updateOrCreate(['name' => 'manage trips']);
        Permission::updateOrCreate(['name' => 'assign trips']);
        Permission::updateOrCreate(['name' => 'reassign trips']);
        Permission::updateOrCreate(['name' => 'unassign trips']);
        Permission::updateOrCreate(['name' => 'view trip statistics']);

        // Driver Portal Permissions
        Permission::updateOrCreate(['name' => 'driver portal']);
        Permission::updateOrCreate(['name' => 'start trips']);
        Permission::updateOrCreate(['name' => 'complete trips']);
        Permission::updateOrCreate(['name' => 'view assigned trips']);
        Permission::updateOrCreate(['name' => 'update trip progress']);
        Permission::updateOrCreate(['name' => 'record fuel transactions']);

        // Booking Request Permissions
        Permission::updateOrCreate(['name' => 'create booking requests']);
        Permission::updateOrCreate(['name' => 'view own booking requests']);
        Permission::updateOrCreate(['name' => 'view all booking requests']);
        Permission::updateOrCreate(['name' => 'approve booking requests']);
        Permission::updateOrCreate(['name' => 'decline booking requests']);

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

        // Fuel Transaction Permissions
        Permission::updateOrCreate(['name' => 'list fuel transactions']);
        Permission::updateOrCreate(['name' => 'see fuel transaction details']);
        Permission::updateOrCreate(['name' => 'add fuel transaction']);
        Permission::updateOrCreate(['name' => 'update fuel transaction']);
        Permission::updateOrCreate(['name' => 'delete fuel transaction']);

        // Service Requisition Permissions
        Permission::updateOrCreate(['name' => 'list service requisitions']);
        Permission::updateOrCreate(['name' => 'see service requisition details']);
        Permission::updateOrCreate(['name' => 'add service requisition']);
        Permission::updateOrCreate(['name' => 'update service requisition']);
        Permission::updateOrCreate(['name' => 'delete service requisition']);
        Permission::updateOrCreate(['name' => 'approve service requisitions']);
        Permission::updateOrCreate(['name' => 'decline service requisitions']);

        // Audit Log Permissions
        Permission::updateOrCreate(['name' => 'list audit logs']);
        Permission::updateOrCreate(['name' => 'see audit log details']);
        Permission::updateOrCreate(['name' => 'see user audit logs']);
        Permission::updateOrCreate(['name' => 'see audit logs']); // For department logs

        // Assign all permissions to Admin
        $admin->givePermissionTo(Permission::all());

        // HoD permissions
        $hod->givePermissionTo([
            'see department details',
            'list departments',
            'see vehicle details',
            'list vehicles',
            'see driver details',
            'list drivers',
            'approve booking requests',
            'decline booking requests',
            'view all booking requests',
            'list trips',
            'see trip details',
            'view trip statistics',
        ]);

        //finance director
        $financeDirector->givePermissionTo([
            'see department details',
            'list departments',
            'see vehicle details',
            'list vehicles',
            'see driver details',
            'list drivers',
            'approve booking requests',
            'decline booking requests',
            'view all booking requests',
            'list trips',
            'see trip details',
            'view trip statistics',
            'list service requisitions',
            'see service requisition details',
            'approve service requisitions',
        ]);

        // Registrar permissions
        $registrar->givePermissionTo([
            'see user details',
            'list users',
            'see department details',
            'list departments',
            'see vehicle details',
            'list vehicles',
            'see driver details',
            'list drivers',
            'see vendor details',
            'list vendors',
            'approve booking requests',
            'decline booking requests',
            'view all booking requests',
            'list trips',
            'see trip details',
            'view trip statistics',
            'list fuel transactions',
            'see fuel transaction details',
            'list service requisitions',
            'see service requisition details',
            'approve service requisitions',
        ]);

        // Transport Officer permissions
        $transportOfficer->givePermissionTo([
            'see vehicle details',
            'list vehicles',
            'add vehicle',
            'update vehicle',
            'delete vehicle',
            'see driver details',
            'list drivers',
            'add driver',
            'update driver',
            'delete driver',
            'see vendor details',
            'list vendors',
            'add vendor',
            'update vendor',
            'delete vendor',
            'list trips',
            'see trip details',
            'manage trips',
            'assign trips',
            'reassign trips',
            'unassign trips',
            'view trip statistics',
            'view all booking requests',
            'approve booking requests',
            'list fuel transactions',
            'see fuel transaction details',
            'add fuel transaction',
            'update fuel transaction',
            'delete fuel transaction',
            'list service requisitions',
            'see service requisition details',
            'add service requisition',
            'update service requisition',
            'delete service requisition',
            'approve service requisitions',
        ]);

        // Driver permissions
        $driver->givePermissionTo([
            'driver portal',
            'start trips',
            'complete trips',
            'view assigned trips',
            'update trip progress',
            'record fuel transactions',
        ]);

        // Staff permissions (general users)
        $staff->givePermissionTo([
            'create booking requests',
            'view own booking requests',
        ]);

        $user = User::where('email', 'promisemphoola2@gmail.com')->first();
        if ($user) {
            $user->assignRole('Admin');
        }

        $user = User::where('email', 'chizinga@must.ac.mw')->first();
        if ($user) {
            $user->assignRole($transportOfficer);
        }

        $user = User::where('email', 'registrar@must.ac.mw')->first();
        if ($user) {
            $user->assignRole($registrar);
        }

        $user = User::where('email', 'dof@must.ac.mw')->first();
        if ($user) {
            $user->assignRole($financeDirector);
        }
    }
}
