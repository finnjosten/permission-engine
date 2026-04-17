<?php

namespace Finn\PermissionEngine\Jobs;

use Finn\PermissionEngine\Models\Permission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SyncDiscoveredPermissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param string[] $permissions The permission names discovered during a request.
     */
    public function __construct(
        public array $permissions,
    ) {}

    public function handle(): void
    {
        $now = Carbon::now();

        foreach ($this->permissions as $name) {
            Permission::updateOrCreate(
                ['name' => $name],
                ['last_seen_at' => $now],
            );
        }

        // Generate wildcard variants for each discovered permission
        $wildcards = $this->generateWildcardVariants();

        foreach ($wildcards as $wildcard) {
            Permission::updateOrCreate(
                ['name' => $wildcard],
                ['last_seen_at' => $now],
            );
        }
    }

    /**
     * Generate wildcard and deny variants from discovered permissions.
     *
     * "post.create.special" -> !post.create.special, post.create.*, !post.create.*, post.*, !post.*, *, !*
     */
    protected function generateWildcardVariants(): array
    {
        $variants = [];

        foreach ($this->permissions as $name) {
            // Skip if already a wildcard or deny
            if (str_contains($name, '*') || str_starts_with($name, '!')) {
                continue;
            }

            $segments = explode('.', $name);

            for ($i = count($segments) - 1; $i >= 1; $i--) {
                $prefix = implode('.', array_slice($segments, 0, $i));
                $variants["{$prefix}.*"] = true;
                $variants["!{$prefix}.*"] = true;
            }

            $variants['*'] = true;
            $variants['!*'] = true;
            $variants["!{$name}"] = true;
        }

        return array_keys($variants);
    }
}
