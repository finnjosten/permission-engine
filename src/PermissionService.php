<?php

namespace Finn\PermissionEngine;

class PermissionService
{
    protected ?array $permissions = null;

    /**
     * Load permission strings for the current request.
     */
    public function load(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    /**
     * Check if the loaded permissions grant access to a given permission.
     *
     * Priority: deny rules always win → allow rules → denied by default.
     */
    public function has(string $permission): bool
    {
        if ($this->permissions === null) {
            return false;
        }

        // Build the deny and allow variants to check
        $segments = explode('.', $permission);
        $denyChecks = $this->buildDenyChecks($segments);
        $allowChecks = $this->buildAllowChecks($segments);

        // Deny rules always win
        foreach ($denyChecks as $deny) {
            if (in_array($deny, $this->permissions, true)) {
                return false;
            }
        }

        // Allow rules
        foreach ($allowChecks as $allow) {
            if (in_array($allow, $this->permissions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build deny check strings for a permission.
     *
     * For "post.create.special" → ["!post.create.special", "!post.create.*", "!post.*", "!*"]
     */
    protected function buildDenyChecks(array $segments): array
    {
        $checks = ['!' . implode('.', $segments)];

        // Walk from most specific wildcard to least
        for ($i = count($segments) - 1; $i >= 1; $i--) {
            $checks[] = '!' . implode('.', array_slice($segments, 0, $i)) . '.*';
        }

        $checks[] = '!*';

        return $checks;
    }

    /**
     * Build allow check strings for a permission.
     *
     * For "post.create.special" → ["post.create.special", "post.create.*", "post.*", "*"]
     */
    protected function buildAllowChecks(array $segments): array
    {
        $checks = [implode('.', $segments)];

        for ($i = count($segments) - 1; $i >= 1; $i--) {
            $checks[] = implode('.', array_slice($segments, 0, $i)) . '.*';
        }

        $checks[] = '*';

        return $checks;
    }

    /**
     * Get the currently loaded permissions.
     */
    public function getPermissions(): ?array
    {
        return $this->permissions;
    }

    /**
     * Clear permissions (end of request).
     */
    public function clear(): void
    {
        $this->permissions = null;
    }
}
