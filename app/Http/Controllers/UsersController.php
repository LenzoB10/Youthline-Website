<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUsersRequest;
use App\Models\User;
use App\Traits\HasDataTable;
use App\Http\Requests\DatatableRequest;
use App\Notifications\AccountStatusUpdatedNotification;
use App\Notifications\UserCreatedPasswordNotification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UsersController extends Controller implements HasMiddleware
{
    use HasDataTable;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:list users', only: ['index']),
            new Middleware('permission:see user details', only: ['show']),
            new Middleware('permission:add user', only: ['store']),
            new Middleware('permission:update user', only: ['edit', 'update']),
            new Middleware('permission:suspend user', only: ['activate', 'ban'])
        ];
    }

    public function index(DatatableRequest $request)
    {
        $query = User::query();

        $users = $this->processDataTable(
            $query,
            $request,
            ['name', 'email', 'status'], // Searchable fields
            [
                'status' => 'status', // Simple where clause
            ]
        );

        $users->appends($request->query());

        return inertia('Users/Index', [
            'users' => $users,
            'filters' => $request->all(),
            'roles' => fn() => Role::get(['id', 'name']), //optional, only called when needed
        ]);
    }

    public function store(CreateUsersRequest $request)
    {
        $password = Str::password(16);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole($request->role);

        $user->notify(new UserCreatedPasswordNotification($password));

        session()->flash('success', 'User created successfully');

        return redirect()->route('admin.users.index');
    }

    public function show(User $user)
    {
        $roles = Role::all()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name
            ];
        });

        $all_permissions = cache()->rememberForever('all_permissions', function () {
            return Permission::all()->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name
                ];
            });
        });

        $user->load('roles');

        $user_permissions_via_role = $user->getPermissionsViaRoles()->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name
            ];
        });

        $user_direct_permissions = $user->getDirectPermissions()->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name
            ];
        });

        $logs = \Spatie\Activitylog\Models\Activity::where('causer_id', $user->id)->latest()->get();

        return inertia('Users/Show', [
            'user' => $user,
            'roles' => $roles,
            'all_permissions' => $all_permissions,
            'user_permissions_via_role' => $user_permissions_via_role,
            'user_direct_permissions' => $user_direct_permissions,
            'logs' => $logs,
        ]);
    }

    public function update(CreateUsersRequest $request, User $user)
    {
        $user->update([
            'name' => $request->name,
            'account_type' => $request->account_type,
        ]);

        $user->syncRoles($request->role);

        session()->flash('success', 'User updated successfully');

        return redirect()->route('admin.users.index');
    }

    public function destroy(User $user)
    {
        $user->delete();

        session()->flash('success', 'User deleted successfully');

        return redirect()->route('admin.users.index');
    }

    public function activate(User $user)
    {
        $user->activate();

        $user->notify(new AccountStatusUpdatedNotification("Active"));

        session()->flash('success', 'User activated successfully');

        return redirect()->route('admin.users.show', $user);
    }

    public function ban(User $user)
    {
        $user->ban();

        $user->notify(new AccountStatusUpdatedNotification("Banned"));

        session()->flash('success', 'User banned successfully');

        return redirect()->route('admin.users.show', $user);
    }
}
