<?php

namespace Finn\PermissionEngine\Commands;

use Finn\PermissionEngine\Commands\Concerns\ResolvesRoles;
use Finn\PermissionEngine\Models\Permission;
use Finn\PermissionEngine\Models\Role;
use Illuminate\Console\Command;

class RolePermissions extends Command
{
    use ResolvesRoles;

    protected $signature = 'role:permissions
                            {role? : Role name or id}
                            {--add=* : Permission to add (repeatable)}
                            {--remove=* : Permission to remove (repeatable)}
                            {--sync : Replace all permissions with the --add list}';

    protected $description = 'Add or remove permissions on a role';

    public function handle(): int
    {
        $role = $this->resolveRole($this->argument('role'));

        if (!$role) {
            return self::FAILURE;
        }

        $add = $this->option('add');
        $remove = $this->option('remove');

        // Flag mode
        if (!empty($add) || !empty($remove) || $this->option('sync')) {
            return $this->apply($role, $add, $remove, (bool) $this->option('sync'));
        }

        if (!$this->input->isInteractive()) {
            $this->error('Pass --add, --remove or --sync when running non-interactively.');
            return self::FAILURE;
        }

        // Interactive loop
        while (true) {
            $current = $role->permissions()->orderBy('name')->pluck('name')->all();
            $this->line("<info>{$role->name}</info>: " . (empty($current) ? '<comment>no permissions</comment>' : implode(', ', $current)));

            $action = $this->choice('What do you want to do?', ['Add', 'Remove', 'Done'], 'Done');

            if ($action === 'Done') {
                return self::SUCCESS;
            }

            if ($action === 'Add') {
                $options = Permission::whereNotIn('name', $current)->orderBy('name')->pluck('name')->all();
                $this->apply($role, $this->pickPermissions('Which permissions to add?', $options), [], false);
            } else {
                $this->apply($role, [], $this->pickPermissions('Which permissions to remove?', $current), false);
            }
        }
    }

    /**
     * @param string[] $add
     * @param string[] $remove
     */
    protected function apply(Role $role, array $add, array $remove, bool $sync): int
    {
        $toAdd = $this->resolvePermissions($add);
        $toRemove = $this->resolvePermissions($remove);

        if ($toAdd === null || $toRemove === null) {
            return self::FAILURE;
        }

        if ($sync) {
            $role->permissions()->sync($toAdd->pluck('id'));
        } else {
            $role->permissions()->syncWithoutDetaching($toAdd->pluck('id'));
            $role->permissions()->detach($toRemove->pluck('id'));
        }

        $this->info(sprintf(
            "Role '%s' updated: %s%d added, %d removed.",
            $role->name,
            $sync ? 'synced, ' : '',
            $toAdd->count(),
            $toRemove->count(),
        ));

        return self::SUCCESS;
    }
}
