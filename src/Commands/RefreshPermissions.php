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

            // Generate wildcard variants
            if (!str_contains($name, '*') && !str_starts_with($name, '!')) {
                $segments = explode('.', $name);

                for ($i = count($segments) - 1; $i >= 1; $i--) {
                    $prefix = implode('.', array_slice($segments, 0, $i));
                    $wildcards["{$prefix}.*"] = true;
                    $wildcards["!{$prefix}.*"] = true;
                }

                $wildcards['*'] = true;
                $wildcards['!*'] = true;
                $wildcards["!{$name}"] = true;
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
     * Scan PHP files for permission('...') calls.
     *
     * @return string[]
     */
    protected function scanForPermissions(): array
    {
        $paths = [
            app_path(),
            base_path('routes'),
        ];

        $permissions = [];

        foreach ($paths as $path) {
            if (!File::isDirectory($path)) {
                continue;
            }

            $finder = Finder::create()->files()->name('*.php')->in($path);

            foreach ($finder as $file) {
                $content = $file->getContents();

                // Match permission('...') and permission("...")
                if (preg_match_all('/\bpermission\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches)) {
                    foreach ($matches[1] as $match) {
                        $permissions[$match] = true;
                    }
                }

                // Match $user->can('...') and Gate::allows('...')
                if (preg_match_all('/(?:->can|Gate::allows|Gate::check|Gate::authorize)\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches)) {
                    foreach ($matches[1] as $match) {
                        $permissions[$match] = true;
                    }
                }
            }
        }

        return array_keys($permissions);
    }
}
