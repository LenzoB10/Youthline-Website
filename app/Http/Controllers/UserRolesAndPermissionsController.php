<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('permission:add user permissions', only: ['changePermissions'])]
#[Middleware('permission:update user role', only: ['changeRole'])]
class UserRolesAndPermissionsController extends Controller
{
    public function changeRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $user->syncRoles($request->{'role'});

        cache()->forget("user.{$user->id}.permissions");

        // Log activity
        activity()
            ->event('updated')
            ->performedOn($user)
            ->causedBy($request->user())
            ->withProperties([
                'old_role' => $user->roles->pluck('name')->toArray(),
                'new_role' => $request->role,
            ])
            ->log('User role updated');

        session()->flash('success', 'Role changed successfully');

        return redirect()->back();
    }

    public function changePermissions(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'permissions' => 'required|array|min:1',
        ]);

        $user->syncPermissions($request->{'permissions'});

        cache()->forget("user.{$user->id}.permissions");

        // Log activity
        activity()
            ->event('updated')
            ->performedOn($user)
            ->causedBy($request->user())
            ->withProperties([
                'old_permissions' => $user->permissions->pluck('name')->toArray(),
                'new_permissions' => $request->permissions,
            ])
            ->log('User permissions updated');

        session()->flash('success', 'Permissions changed successfully');

        return redirect()->back();
    }
}
