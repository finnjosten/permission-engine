<?php

namespace Finn\PermissionEngine\Commands;

use Finn\PermissionEngine\Commands\Concerns\ResolvesRoles;
use Finn\PermissionEngine\Models\Permission;
use Finn\PermissionEngine\Models\Role;
use Illuminate\Console\Command;

class CreateRole extends Command
{
    use ResolvesRoles;

    protected $signature = 'role:create
                            {name? : The name of the role}
                            {--permission=* : Permission to attach (repeatable)}';

    protected $description = 'Create a new role and optionally attach permissions';

    public function handle(): int
    {
        $name = trim($this->argument('name') ?? $this->ask('What is the name of the role?') ?? '');

        if ($name === '') {
            $this->error('A role name is required.');
            return self::FAILURE;
        }

        if ($this->findRole($name)) {
            $this->error("Role '{$name}' already exists.");
            return self::FAILURE;
        }

        $names = $this->option('permission');

        if (empty($names) && $this->input->isInteractive() && $this->confirm('Attach permissions now?', true)) {
            $names = $this->pickPermissions('Which permissions?', Permission::orderBy('name')->pluck('name')->all());
        }

        $permissions = $this->resolvePermissions($names);

        if ($permissions === null) {
            return self::FAILURE;
        }

        $role = Role::create(['name' => $name]);
        $role->permissions()->sync($permissions->pluck('id'));

        $this->info("Role '{$role->name}' created with {$permissions->count()} permission(s).");

        return self::SUCCESS;
    }
}
