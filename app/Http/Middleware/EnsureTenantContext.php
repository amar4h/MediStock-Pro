<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    /**
     * Set tenant context and verify the tenant is active.
     *
     * Combines tenant scope setting with subscription validation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->tenant_id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant context not found.',
                ], 403);
            }

            return redirect()->route('login');
        }

        $tenant = $user->tenant;

        if (! $tenant) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant not found.',
                ], 403);
            }

            return redirect()->route('login');
        }

        // Check subscription status
        $status = $tenant->subscription_status;

        if ($status === 'active') {
            // OK
        } elseif ($status === 'trial' && $tenant->trial_ends_at && $tenant->trial_ends_at->isFuture()) {
            // Trial still valid
        } else {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your subscription is inactive. Please renew.',
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Your subscription is inactive. Please renew.',
            ]);
        }

        // Set tenant context
        config(['app.tenant_id' => $user->tenant_id]);
        app()->instance('tenant.id', $user->tenant_id);

        return $next($request);
    }
}
