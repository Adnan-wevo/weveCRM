<?php

namespace App\Http\Middleware;

use App\Concerns\ResolveTenantRouteParam;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks non-super-admin tenant users from accessing central routes.
 *
 * Users who are registered under a specific tenant should only use the app
 * through that tenant's URL (/{tenant}/… in path mode). If they somehow reach
 * a central route, they are redirected to their tenant's dashboard.
 *
 * Super-admins are organisation-wide administrators and always have access
 * to the central app.
 *
 * In single-tenant mode this middleware is a no-op because there are no
 * tenant routes to redirect to.
 */
class EnsureCentralAccess
{
    use ResolveTenantRouteParam;

    public function handle(Request $request, Closure $next): Response
    {
        // Single-tenant mode: no tenant routing exists — always pass through.
        if ($this->isSingleTenantMode()) {
            return $next($request);
        }

        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        // Super-admins always have access to the central app.
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        // If this user is linked to a tenant, redirect them to their tenant's app.
        $tenant = $user->tenants()->first();

        if ($tenant !== null) {
            /** @var \App\Models\Tenant $tenant */
            return redirect(route('tenant.dashboard', ['tenant' => $this->tenantRouteParam($tenant)]));
        }

        return $next($request);
    }
}
