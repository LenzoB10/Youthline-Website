<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// permission:list roles - intentionally left unrestricted
#[Middleware('permission:see role details', only: ['show'])]
#[Middleware('permission:add role', only: ['create', 'store'])]
#[Middleware('permission:update role', only: ['edit', 'update'])]
#[Middleware('permission:delete role', only: ['destroy'])]
class RolesController extends Controller
{
    public function index(): Response
    {
        $roles = Role::withCount('users')
            ->with('permissions')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                        ];
                    }),
                ];
            });

        $permissions = Permission::all()->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
            ];
        });

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function show(Role $role): Response
    {

        $role->load('permissions', 'users');

        return Inertia::render('Roles/Show', compact('role'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'required|array|min:1',
        ]);

        $role = Role::create(['name' => $request->{'name'}]);

        $role->syncPermissions($request->{'permissions'});

        // Log activity
        activity()
            ->event('created')
            ->performedOn($role)
            ->causedBy($request->user())
            ->withProperties([
                'name' => $request->name,
                'permissions' => $request->permissions,
            ])
            ->log('Role created');

        session()->flash('success', 'Role created successfully');

        return redirect()->route('admin.roles.index');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $request->validate([
            'name' => 'required|unique:roles,name,'.$role->id,
            'permissions' => 'required|array|min:1',
        ]);

        // Check if the role is Admin
        if ($role->name === 'Admin' || $role->name === 'Registrar' || $role->name === 'Transport Officer' || $role->name === 'HoD') {
            // Only update permissions, not the name
            $role->syncPermissions($request->permissions);

            // Log activity
            activity()
                ->event('updated')
                ->performedOn($role)
                ->causedBy($request->user())
                ->withProperties([
                    'old_permissions' => $role->permissions->pluck('name')->toArray(),
                    'new_permissions' => $request->permissions,
                ])
                ->log('Updated permissions for protected role');

            session()->flash('success', 'Permissions updated successfully');

            return redirect()->route('admin.roles.index');
        }

        // For other roles, update both name and permissions
        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        // Log activity
        activity()
            ->event('updated')
            ->performedOn($role)
            ->causedBy($request->user())
            ->withProperties([
                'old_name' => $role->name,
                'new_name' => $request->name,
                'old_permissions' => $role->permissions->pluck('name')->toArray(),
                'new_permissions' => $request->permissions,
            ])
            ->log('Role updated');

        session()->flash('success', 'Role updated successfully');

        return redirect()->route('admin.roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'Admin' || $role->name === 'Registrar' || $role->name === 'Transport Officer' || $role->name === 'HoD') {
            session()->flash('error', 'You cannot delete the system role');

            return redirect()->route('admin.roles.index');
        }

        if ($role->users()->count() > 0) {
            session()->flash('error', 'You cannot delete a role with users');

            return redirect()->route('admin.roles.index');
        }

        // Log activity
        activity()
            ->event('deleted')
            ->performedOn($role)
            ->causedBy(Auth::user())
            ->log($role->name.' Role deleted');

        $role->delete();

        session()->flash('success', 'Role deleted successfully');

        return redirect()->route('admin.roles.index');
    }
}
