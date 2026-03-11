<?php

namespace App\Http\Responses;

use App\Concerns\ResolveTenantRouteParam;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    use ResolveTenantRouteParam;

    /**
     * After a successful login, route the user to the right app context.
     *
     * - In single-tenant mode everyone lands on the central dashboard.
     * - Non-super-admin users who belong to a tenant are sent to their
     *   tenant's dashboard (subdomain slug or path ID, resolved automatically).
     * - Super-admins and central-only users land on the central dashboard.
     */
    public function toResponse($request): RedirectResponse
    {
        $user = auth()->user();

        // Single-tenant mode: no tenant routing — go straight to central.
        if (! $this->isSingleTenantMode() && ! $user->hasRole('super-admin')) {
            $tenant = $user->tenants()->first();

            if ($tenant !== null) {
                /** @var \App\Models\Tenant $tenant */
                return redirect(route('tenant.dashboard', ['tenant' => $this->tenantRouteParam($tenant)]));
            }
        }

        // Central users (no tenant), super-admins, and single-tenant mode all
        // honour the intended URL, falling back to the central dashboard.
        return redirect()->intended(route('dashboard'));
    }
}
