<?php

namespace App\Http\Middleware;

use App\Concerns\ResolveTenantRouteParam;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user is allowed inside the current tenant.
 *
 * - Super-admins are redirected to the central app; they manage the platform
 *   itself and should not share tenant-scoped pages with regular tenant users.
 *
 * - Authenticated users who are NOT members of the current tenant are
 *   redirected to their own tenant dashboard (or to the home page if they
 *   have no tenant at all).
 *
 * Only relevant when TENANCY_MODE is "path" or "subdomain".
 * In single-tenant mode this middleware is a no-op.
 */
class EnsureTenantAccess
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

        // Super-admins belong to the central app — send them back there.
        if ($user->hasRole('super-admin')) {
            return redirect()->route('dashboard');
        }

        // Verify the user is actually a member of the current tenant.
        $currentTenantId = tenancy()->initialized ? tenant('id') : null;

        if ($currentTenantId && ! $user->tenants()->where('id', $currentTenantId)->exists()) {
            // Redirect to the user's own tenant dashboard, or home if they have none.
            $ownTenant = $user->tenants()->first();

            if ($ownTenant) {
                /** @var \App\Models\Tenant $ownTenant */
                return redirect(route('tenant.dashboard', ['tenant' => $this->tenantRouteParam($ownTenant)]));
            }

            return redirect()->route('home');
        }

        return $next($request);
    }
}
