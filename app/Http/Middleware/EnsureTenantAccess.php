<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;

/**
 * EnsureTenantAccess Middleware
 *
 * CRITICAL SECURITY: This middleware ensures multi-tenant data isolation.
 * It identifies the tenant and ensures users can only access their tenant's data.
 */

class EnsureTenantAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

        /**
     * Handle an incoming request.
     *
     * This middleware runs on every API request to:
     * 1. Identify which tenant is making the request
     * 2. Set the tenant context globally
     * 3. Ensure users can only access their tenant's data
     */

    public function handle(Request $request, Closure $next): Response
    {
        $resolvedTenant = null;

        // 1) If authenticated, prefer user's tenant (and require it to be active)
        if ($user = $request->user()) {
            $resolvedTenant = $user->tenant;
            if (!$resolvedTenant || !$resolvedTenant->is_active) {
                return response()->json(['error' => 'Tenant not found or inactive'], 403);
            }
        }

        // 2) Subdomain -> {slug}.<BASE>
        $host = $request->getHost();
        $base = config('app.tenant_base', env('TENANT_BASE_DOMAIN', 'rmm.com'));
        if (preg_match('/^([^.]+)\.' . preg_quote($base, '/') . '$/i', $host, $m)) {
            $bySub = Tenant::where('slug', $m[1])->active()->first();
            if ($bySub) {
                // If user is logged in, ensure match
                if ($resolvedTenant && $bySub->id !== $resolvedTenant->id) {
                    return response()->json(['error' => 'Tenant context mismatch'], 409);
                }
                $resolvedTenant = $bySub;
            }
        }

        // 3) Custom domain
        if (!$resolvedTenant) {
            $byDomain = Tenant::where('domain', $host)->active()->first();
            if ($byDomain) {
                if ($user && $byDomain->id !== $user->tenant_id) {
                    return response()->json(['error' => 'Tenant context mismatch'], 409);
                }
                $resolvedTenant = $byDomain;
            }
        }

        // 4) Dev headers (ID or Slug)
        if (!$resolvedTenant) {
            if ($id = $request->header('X-Tenant-ID')) {
                $byId = Tenant::active()->find($id);
                if ($byId) $resolvedTenant = $byId;
            } elseif ($slug = $request->header('X-Tenant-Slug')) {
                $bySlug = Tenant::where('slug', $slug)->active()->first();
                if ($bySlug) $resolvedTenant = $bySlug;
            }
        }

        // If user is logged in but we still can’t resolve a tenant, block
        if ($user && !$resolvedTenant) {
            return response()->json(['error' => 'Unable to determine tenant context'], 403);
        }

        // Public endpoints can pass without tenant; protected routes apply this middleware anyway.
        if (!$resolvedTenant) {
            return $next($request);
        }

        // Set tenant context globally (both tenant and tenant_id)
        app()->instance('tenant', $resolvedTenant);
        app()->instance('tenant_id', $resolvedTenant->id);
        config(['app.tenant' => $resolvedTenant]);

        // Lightweight last-activity update (avoid noisy events)
        if (method_exists($resolvedTenant, 'updateLastActivity')) {
            $resolvedTenant->updateLastActivity(); // implement saveQuietly inside the model
        }

        return $next($request);
    }

}

