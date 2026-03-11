<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Initialise tenancy for Livewire update requests.
 *
 * Livewire's /livewire/update endpoint is a central route and never goes through
 * InitializeTenancyByPath, so authorize() calls inside components break for
 * tenant-only users. This middleware reads the Referer header (the original
 * page URL, e.g. /x1/access-control/users) and extracts the tenant ID from the
 * first path segment, allowing permission checks to resolve tenant-scoped records.
 *
 * Only active in "path" tenancy mode.
 */
class InitializeTenancyFromLivewireUpdate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            config('tenancy.mode', 'single') === 'path'
            && ! tenancy()->initialized
            && $request->hasHeader('X-Livewire')
        ) {
            $referer = $request->header('Referer', '');
            $refererPath = $referer ? (parse_url($referer, PHP_URL_PATH) ?? '') : '';
            $segments = array_values(array_filter(explode('/', $refererPath)));
            $tenantId = $segments[0] ?? null;

            if ($tenantId) {
                $tenant = Tenant::find($tenantId);
                if ($tenant) {
                    tenancy()->initialize($tenant);
                }
            }
        }

        return $next($request);
    }
}
