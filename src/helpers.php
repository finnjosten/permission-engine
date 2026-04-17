<?php

use Finn\PermissionEngine\PermissionRegistrar;

if (!function_exists('permission')) {
    /**
     * Register a permission for discovery tracking.
     * This does NOT check permissions — it marks a permission as "used in code".
     */
    function permission(string $permission): void
    {
        app(PermissionRegistrar::class)->register($permission);
    }
}
