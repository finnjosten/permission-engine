<?php

namespace Finn\PermissionEngine\Commands\Concerns;

use Finn\PermissionEngine\Models\Permission;
use Finn\PermissionEngine\Models\Role;
use Illuminate\Support\Collection;

trait ResolvesRoles
{
    /**
     * Find a role by id or (case-insensitive) name.
     */
    protected function findRole(string $value): ?Role
    {
        if (ctype_digit($value) && $role = Role::find((int) $value)) {
            return $role;
        }

        return Role::whereRaw('LOWER(name) = ?', [strtolower($value)])->first();
    }

    /**
     * Ask for a role when none was given, or resolve the given one.
     */
    protected function resolveRole(?string $value): ?Role
    {
        if ($value === null) {
            if (!$this->input->isInteractive()) {
                $this->error('A role is required when running non-interactively.');
                return null;
            }

            $roles = Role::orderBy('name')->pluck('name')->all();

            if (empty($roles)) {
                $this->error('No roles exist yet. Create one with role:create.');
                return null;
            }

            $value = $this->choice('Which role?', $roles);
        }

        $role = $this->findRole($value);

        if (!$role) {
            $this->error("Role '{$value}' not found.");
        }

        return $role;
    }

    /**
     * Resolve permission names to models. Returns null (and prints the
     * unknown names) if any of them do not exist.
     *
     * @param string[] $names
     */
    protected function resolvePermissions(array $names): ?Collection
    {
        $names = array_values(array_unique(array_filter(array_map('trim', $names))));
        $roots = array_intersect($names, ['*', '!*']);

        $permissions = Permission::whereIn('name', $names)->get();
        $unknown = array_diff($names, $permissions->pluck('name')->all(), $roots);

        if (!empty($unknown)) {
            $this->error('Unknown permission(s): ' . implode(', ', $unknown));
            $this->line('Run <comment>php artisan permission:refresh</comment> to discover permissions from the codebase.');
            return null;
        }

        // Root wildcards are always valid, make sure they exist
        foreach ($roots as $root) {
            if (!$permissions->contains('name', $root)) {
                $permissions->push(Permission::firstOrCreate(['name' => $root]));
            }
        }

        return $permissions;
    }

    /**
     * Interactive multi-select of permissions.
     *
     * @param string[] $options
     * @return string[]
     */
    protected function pickPermissions(string $question, array $options): array
    {
        if (empty($options)) {
            $this->warn('No permissions to choose from.');
            return [];
        }

        return (array) $this->choice($question . ' (comma separated)', $options, null, null, true);
    }
}
