<?php

namespace Finn\PermissionEngine;

use Finn\PermissionEngine\Commands\RefreshPermissions;
use Finn\PermissionEngine\Jobs\SyncDiscoveredPermissions;
use Finn\PermissionEngine\Middleware\LoadPermissions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PermissionEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/permission-engine.php', 'permission-engine');

        $this->app->singleton(PermissionService::class);
        $this->app->singleton(PermissionRegistrar::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/permission-engine.php' => config_path('permission-engine.php'),
        ], 'permission-engine-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'permission-engine-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                RefreshPermissions::class,
            ]);
        }

        $this->registerGate();
        $this->registerMiddleware();
        $this->registerTerminating();
    }

    protected function registerGate(): void
    {
        Gate::before(function ($user, string $ability) {
            $service = app(PermissionService::class);

            // Load permissions on first check if not already loaded
            if ($service->getPermissions() === null && method_exists($user, 'getPermissions')) {
                $service->load($user->getPermissions());
            }

            // Register permission for discovery
            app(PermissionRegistrar::class)->register($ability);

            if ($service->getPermissions() === null) {
                return null; // Let other gates handle it
            }

            return $service->has($ability) ? true : false;
        });
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('permissions', LoadPermissions::class);
    }

    protected function registerTerminating(): void
    {
        $this->app->terminating(function () {
            $registrar = app(PermissionRegistrar::class);
            $service = app(PermissionService::class);

            $discovered = $registrar->getDiscovered();

            if (!empty($discovered)) {
                SyncDiscoveredPermissions::dispatch($discovered);
            }

            $service->clear();
            $registrar->clear();
        });
    }
}
