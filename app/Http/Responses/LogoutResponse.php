<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * Redirect through Keycloak's end-session endpoint after Fortify logs the
     * user out so the SSO session is also terminated.
     *
     * Note: the session is already invalidated by Fortify before this response
     * is created, so we cannot read id_token_hint here. Keycloak will still
     * terminate the session using the browser's SSO cookie.
     */
    public function toResponse($request): RedirectResponse
    {
        // The session is already invalidated by Fortify at this point, so
        // read the tenant login URL from the POST body (passed as a hidden field).
        // Falls back to central home for non-tenant logins.
        $afterLogout = $request->input('_tenant_login') ?: url('/');

        // The id_token was captured into the app container by CheckKeycloakBackchannelLogout.
        $idToken = app()->bound('keycloak_id_token') ? app('keycloak_id_token') : null;

        // Local-only users (no Keycloak session) go straight to the login page.
        if (! $idToken) {
            return redirect($afterLogout);
        }

        $baseUrl = rtrim((string) config('services.keycloak.base_url'), '/');
        $realm = config('services.keycloak.realms', 'master');
        $client = config('services.keycloak.client_id');

        // For Keycloak users, redirect back to the correct login page after the
        // SSO session ends (tenant login for tenant users, central home otherwise).
        $url = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/logout"
            .'?client_id='.urlencode((string) $client)
            .'&post_logout_redirect_uri='.urlencode($afterLogout)
            .'&id_token_hint='.urlencode($idToken);

        return redirect()->away($url);
    }
}
