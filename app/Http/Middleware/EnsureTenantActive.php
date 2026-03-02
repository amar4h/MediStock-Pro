<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
{
    /**
     * Ensure the authenticated user's tenant has an active subscription.
     *
     * Allowed statuses: 'active', 'trial' (if trial_ends_at is in the future).
     * Blocks with 403 if tenant subscription is 'expired', 'inactive', or
     * if the trial period has ended.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found. Please contact support.',
            ], 403);
        }

        $tenant = $user->tenant;
        $status = $tenant->subscription_status;

        // Active subscription — allow
        if ($status === 'active') {
            return $next($request);
        }

        // Trial — check if trial period is still valid
        if ($status === 'trial') {
            if ($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture()) {
                return $next($request);
            }

            return response()->json([
                'success' => false,
                'message' => 'Your trial period has ended. Please subscribe to continue.',
            ], 403);
        }

        // Expired, inactive, or any other status — block
        return response()->json([
            'success' => false,
            'message' => 'Your subscription is inactive. Please renew.',
        ], 403);
    }
}
