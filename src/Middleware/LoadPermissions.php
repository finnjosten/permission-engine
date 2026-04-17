<?php

namespace Finn\PermissionEngine\Middleware;

use Closure;
use Finn\PermissionEngine\PermissionService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoadPermissions
{
    public function __construct(
        protected PermissionService $service,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && method_exists($user, 'getPermissions')) {
            $this->service->load($user->getPermissions());
        }

        return $next($request);
    }
}
