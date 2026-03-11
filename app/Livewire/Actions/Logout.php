<?php

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     *
     * If the user logged in via Keycloak (id_token present in session) the
     * browser is sent through Keycloak's end-session endpoint so the SSO
     * session is also terminated. Local-only users go straight to home.
     *
     * Returns `mixed` to accommodate both standard `RedirectResponse` and
     * Livewire's own `Redirector` proxy when called from a Livewire component.
     */
    public function __invoke(): mixed
    {
        // Capture the id_token before the session is destroyed.
        $idToken = Session::get('keycloak_id_token');

        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();

        if ($idToken) {
            return redirect()->away($this->buildKeycloakLogoutUrl($idToken));
        }

        return redirect('/');
    }

    /**
     * Build the Keycloak end-session URL.
     *
     * Keycloak will terminate the SSO session and redirect the browser back to
     * the application's home page. The id_token_hint is optional but allows
     * Keycloak to skip the "do you want to log out?" confirmation page.
     */
    private function buildKeycloakLogoutUrl(?string $idToken): string
    {
        $baseUrl = rtrim((string) config('services.keycloak.base_url'), '/');
        $realm = config('services.keycloak.realms', 'master');
        $client = config('services.keycloak.client_id');

        $url = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/logout"
            .'?client_id='.urlencode((string) $client)
            .'&post_logout_redirect_uri='.urlencode(url('/'));

        if ($idToken) {
            $url .= '&id_token_hint='.urlencode($idToken);
        }

        return $url;
    }
}
