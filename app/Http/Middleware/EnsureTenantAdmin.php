<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureTenantAdmin Middleware
 *
 * This middleware ensures that only tenant administrators can access
 * sensitive administrative functions within their tenant.
 */

class EnsureTenantAdmin
{
    /**
     * Handle an incoming request.
     *
     * Checks if the authenticated user is a tenant admin
     * before allowing access to administrative functions.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['error' => 'Account is inactive'], 403);
        }

        if (!$user->tenant || !$user->tenant->is_active) {
            return response()->json(['error' => 'Tenant account is inactive'], 403);
        }

        if (!$user->is_tenant_admin) {
            return response()->json([
                'error' => 'Access denied. Tenant administrator privileges required.',
                'required_role' => 'tenant_admin',
                'user_is_admin' => (bool) $user->is_tenant_admin,
            ], 403);
        }

        return $next($request);
    }
}
