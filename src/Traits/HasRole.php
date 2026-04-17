<?php

namespace Finn\PermissionEngine\Traits;

use Finn\PermissionEngine\Models\Role;

trait HasRole
{
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the flat array of permission strings from the user's role.
     *
     * @return string[]
     */
    public function getPermissions(): array
    {
        $role = $this->relationLoaded('role') ? $this->role : $this->load('role.permissions')->role;

        if (!$role) {
            return [];
        }

        if (!$role->relationLoaded('permissions')) {
            $role->load('permissions');
        }

        return $role->permissions->pluck('name')->all();
    }
}
