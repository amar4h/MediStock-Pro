<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Check if the authenticated user's role has the required permission.
     *
     * Usage in routes:
     *   ->middleware('permission:items.create')
     *   ->middleware('permission:sales.void')
     *
     * The 'owner' role bypasses all permission checks.
     *
     * @param  string  $permission  The permission name (e.g., 'items.create', 'sales.void')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Owner role bypasses all permission checks
        if ($user->role && $user->role->name === 'owner') {
            return $next($request);
        }

        // Check if the user's role has the required permission
        if ($user->hasPermission($permission)) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to perform this action.',
        ], 403);
    }
}
