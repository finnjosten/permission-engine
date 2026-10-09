<?php

namespace Finn\PermissionEngine\Commands;

use Finn\PermissionEngine\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class RefreshPermissions extends Command
{
    protected $signature = 'permission:refresh';

    protected $description = 'Scan codebase for permission() calls and sync to database';

    public function handle(): int
    {
        $this->info('Scanning codebase for permissions...');

        $permissions = $this->scanForPermissions();

        if (empty($permissions)) {
            $this->warn('No permission() calls found.');
            return self::SUCCESS;
        }

        $this->info('Found ' . count($permissions) . ' permission(s):');

        $now = Carbon::now();
        $wildcards = [];

        foreach ($permissions as $name) {
            $this->line("  - {$name}");

            Permission::updateOrCreate(
                ['name' => $name],
                ['last_seen_at' => $now],
            );

            foreach (Permission::variantsFor($name) as $variant) {
                $wildcards[$variant] = true;
            }
        }

        foreach (array_keys($wildcards) as $wildcard) {
            Permission::updateOrCreate(
                ['name' => $wildcard],
                ['last_seen_at' => $now],
            );
        }

        $this->info('Permissions synced successfully.');
        $this->info('Generated ' . count($wildcards) . ' wildcard/deny variant(s).');

        return self::SUCCESS;
    }

    /**
     * Scan PHP files for permission('...') and can('...') calls.
     *
     * @return string[]
     */
    protected function scanForPermissions(): array
    {
        $paths = [
            app_path(),
            base_path('routes'),
        ];

        $property = config('permission-engine.prefix_property', 'permissionPrefix');
        $permissions = [];

        foreach ($paths as $path) {
            if (!File::isDirectory($path)) {
                continue;
            }

            $finder = Finder::create()->files()->name('*.php')->in($path);

            foreach ($finder as $file) {
                $content = $file->getContents();
                $prefix = $property ? $this->detectPrefix($content, $property) : null;

                // Match permission('...') and permission("...")
                if (preg_match_all('/\bpermission\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches)) {
                    foreach ($matches[1] as $match) {
                        $permissions[$match] = true;
                    }
                }

                // Match $this->can('...', ...) and apply the class prefix
                if (preg_match_all('/\$this->can\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,([^)]*))?/', $content, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $ignorePrefix = $this->ignoresPrefix($match[2] ?? '');
                        $permissions[$this->applyPrefix($match[1], $ignorePrefix ? null : $prefix)] = true;
                    }
                }

                // Match $user->can('...') and Gate::allows('...'), taken literally
                if (preg_match_all('/(?:(?<!\$this)->can|Gate::allows|Gate::check|Gate::authorize)\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                    foreach ($matches[1] as $match) {
                        $permissions[$match] = true;
                    }
                }
            }
        }

        return array_keys($permissions);
    }

    /**
     * Find `protected $permissionPrefix = '...';` (any visibility, optional type) in a file.
     */
    protected function detectPrefix(string $content, string $property): ?string
    {
        $pattern = '/(?:public|protected|private)\s+(?:\??string\s+)?\$' . preg_quote($property, '/') . '\s*=\s*[\'"]([^\'"]*)[\'"]/';

        if (!preg_match($pattern, $content, $match)) {
            return null;
        }

        $prefix = trim($match[1], '.');

        return $prefix === '' ? null : $prefix;
    }

    /**
     * Whether the extra arguments of a `$this->can()` call disable the prefix.
     * Expected signature: can(string $permission, bool $abort = true, bool $ignorePrefix = false)
     */
    protected function ignoresPrefix(string $args): bool
    {
        if (preg_match('/\bignorePrefix\s*:\s*true\b/i', $args)) {
            return true;
        }

        // Named arguments other than ignorePrefix say nothing about the prefix
        $positional = array_values(array_filter(
            array_map('trim', explode(',', $args)),
            fn (string $arg) => $arg !== '' && !preg_match('/^\w+\s*:/', $arg),
        ));

        // $args holds everything after the permission, so index 1 is the 3rd argument
        return strtolower($positional[1] ?? '') === 'true';
    }

    /**
     * Mirror of the app's Controller::resolvePermissionName().
     */
    protected function applyPrefix(string $permission, ?string $prefix): string
    {
        if ($prefix === null) {
            return $permission;
        }

        if ($permission === $prefix || str_starts_with($permission, $prefix . '.')) {
            return $permission;
        }

        return $prefix . '.' . ltrim($permission, '.');
    }
}
