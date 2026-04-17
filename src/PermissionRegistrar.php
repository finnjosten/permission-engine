<?php

namespace Finn\PermissionEngine;

class PermissionRegistrar
{
    /**
     * Permissions discovered during this request.
     */
    protected array $discovered = [];

    /**
     * Register a permission as discovered (used in code).
     */
    public function register(string $permission): void
    {
        $this->discovered[$permission] = true;
    }

    /**
     * Get all permissions discovered during this request.
     *
     * @return string[]
     */
    public function getDiscovered(): array
    {
        return array_keys($this->discovered);
    }

    /**
     * Clear discovered permissions.
     */
    public function clear(): void
    {
        $this->discovered = [];
    }
}
