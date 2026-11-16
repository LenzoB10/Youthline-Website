<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class AlertRecipients
{
    /**
     * Everyone who holds a permission (directly or through a role), optionally
     * leaving out the person who caused the alert.
     *
     * @return Collection<int, User>
     */
    public function withPermission(string $permission, ?User $except = null): Collection
    {
        try {
            $users = User::permission($permission)->get();
        } catch (PermissionDoesNotExist) {
            return collect();
        }

        return $except ? $users->reject(fn (User $user) => $user->is($except))->values() : $users;
    }
}
