<?php

namespace Finn\PermissionEngine\Commands;

use Finn\PermissionEngine\Models\Role;
use Illuminate\Console\Command;

class ListRoles extends Command
{
    protected $signature = 'role:list';

    protected $description = 'List all roles with their user count and permissions';

    public function handle(): int
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();

        if ($roles->isEmpty()) {
            $this->warn('No roles found.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Users', 'Permissions'],
            $roles->map(fn (Role $role) => [
                $role->id,
                $role->name,
                $role->users_count,
                $role->permissions->pluck('name')->sort()->implode(', ') ?: '-',
            ]),
        );

        return self::SUCCESS;
    }
}
