<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantScope
{
    /**
     * Set the current tenant context from the authenticated user.
     *
     * Stores the tenant_id in the application config so it is available
     * throughout the request lifecycle. The TenantScope global scope and
     * BelongsToTenant trait rely on Auth::user()->tenant_id, but this
     * middleware also publishes the value to config('app.tenant_id') for
     * use in service classes, queue jobs, and other contexts where the
     * auth user may not be directly accessible.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant_id) {
            // Store tenant_id in runtime config for the current request lifecycle.
            // This allows services, jobs, and helpers to resolve the tenant
            // without coupling directly to the Auth facade.
            config(['app.tenant_id' => $user->tenant_id]);

            // Also bind the tenant_id as a singleton in the container
            // for dependency-injectable access.
            app()->instance('tenant.id', $user->tenant_id);
        }

        return $next($request);
    }
}
