<?php

namespace App\Concerns;

use App\Models\Tenant;

/**
 * Resolves the correct {tenant} route parameter value for the current tenancy
 * mode, so redirects and route() calls work seamlessly across all three modes:
 *
 *   subdomain — {tenant} = subdomain slug derived from the tenant's domain record
 *   path      — {tenant} = tenant ID (used directly as the path prefix)
 *   single    — no tenant routes; callers should skip tenant redirects entirely
 */
trait ResolveTenantRouteParam
{
    /**
     * Returns the value to pass as ['tenant' => …] when generating a tenant route URL.
     *
     * In subdomain mode the route domain is `{tenant}.central.domain`, so the
     * parameter must be the subdomain slug (e.g. "acme"), not the UUID.
     * In path mode the route prefix is `/{tenant}/…`, so the tenant ID is used directly.
     */
    protected function tenantRouteParam(Tenant $tenant): string
    {
        if (config('tenancy.mode') === 'subdomain') {
            $centralDomain = config('tenancy.central_domains')[0] ?? '';
            $domainRecord = $tenant->domains()->first();

            return ($domainRecord && $centralDomain)
                ? str_replace('.'.$centralDomain, '', $domainRecord->domain)
                : (string) $tenant->id;
        }

        // path mode: the {tenant} route parameter IS the tenant ID.
        return (string) $tenant->id;
    }

    /**
     * Returns true when tenant routes do not exist (single-tenant mode).
     * Callers should fall back to central routes in this case.
     */
    protected function isSingleTenantMode(): bool
    {
        return config('tenancy.mode') === 'single';
    }
}
