<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class KeycloakController extends Controller
{
    /**
     * Build a URL on the central domain (APP_URL) for the given path.
     *
     * Using config('app.url') directly — never route() — ensures the correct
     * scheme, host and port are used regardless of whether the current request
     * comes from a tenant subdomain or the central domain.
     */
    private function centralUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }

    /**
     * Force the Socialite Keycloak driver to always use the central-domain callback
     * URL, regardless of which domain (tenant subdomain or central) the user is on.
     *
     * Keycloak validates redirect_uri against its registered URIs list. By always
     * pointing to the central callback we need only one registered URI per flow.
     */
    private function enforceCentralCallback(string $path = '/auth/keycloak/callback'): void
    {
        config(['services.keycloak.redirect' => $this->centralUrl($path)]);
    }

    /**
     * Redirect the user to the Keycloak authentication page.
     *
     * prompt=login forces Keycloak to show the login form even when there is
     * already an active SSO session for a different user.
     */
    public function redirect(): RedirectResponse
    {
        $this->enforceCentralCallback();

        return Socialite::driver('keycloak') // @phpstan-ignore method.notFound
            ->with(['prompt' => 'login'])
            ->redirect();
    }

    /**
     * Redirect the user to Keycloak's dedicated registration endpoint.
     *
     * Keycloak exposes `/protocol/openid-connect/registrations` which always
     * shows the registration form directly. This is more reliable than using
     * `prompt=create` on the `/auth` endpoint, which some Keycloak versions
     * ignore and show the login form instead.
     *
     * If an SSO session already exists, Keycloak may silently skip registration
     * and return a callback immediately — the callback handler creates the local
     * user regardless.
     */
    public function registerRedirect(): RedirectResponse
    {
        $this->enforceCentralCallback();

        $baseUrl = rtrim((string) config('services.keycloak.base_url'), '/');
        $realm = config('services.keycloak.realms', 'wevetel');
        $clientId = config('services.keycloak.client_id');
        $redirectUri = $this->centralUrl('/auth/keycloak/callback');

        // Generate state and store it in the session for CSRF protection,
        // matching the key Socialite uses so the callback can verify it.
        $state = \Illuminate\Support\Str::random(40);
        session()->put('state', $state);

        $url = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/registrations"
            .'?client_id='.urlencode((string) $clientId)
            .'&redirect_uri='.urlencode($redirectUri)
            .'&response_type=code'
            .'&scope=openid'
            .'&state='.$state;

        return redirect()->away($url);
    }

    /**
     * Legacy route kept for backwards compatibility — redirects to the
     * registration endpoint directly (no longer needs a two-step logout flow).
     */
    public function registerFresh(): RedirectResponse
    {
        return $this->registerRedirect();
    }

    /**
     * Handle the callback from Keycloak.
     *
     * Finds or creates a local User, syncs their name/email from Keycloak,
     * assigns the default "user" role to new accounts, and logs them in.
     */
    public function callback(): RedirectResponse
    {
        $this->enforceCentralCallback();

        try {
            $socialUser = Socialite::driver('keycloak')->user();
        } catch (\Throwable $e) {
            Log::error('Keycloak OAuth callback failed', [
                'exception' => get_class($e),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => __('Could not authenticate with Keycloak. Please try again.'),
            ]);
        }

        $isNew = false;

        /** @var User $user */
        $user = User::withTrashed()
            ->where('keycloak_id', $socialUser->getId())
            ->orWhere('email', $socialUser->getEmail())
            ->first();

        // Pull tenant ID from session (set by tenant register route). This cleans up
        // the session key for all flows, including returning users who don't need it.
        $registeringTenantId = session()->pull('registering_tenant_id');

        if ($user === null) { // @phpstan-ignore identical.alwaysFalse
            $user = User::create([
                'keycloak_id' => $socialUser->getId(),
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: $socialUser->getEmail(),
                'email' => $socialUser->getEmail(),
                'password' => null,
                'email_verified_at' => now(),
            ]);

            // Assign default "user" role to brand-new accounts.
            Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
            $user->assignRole('user');

            // Link this new user to the tenant they registered through.
            // enforceCentralCallback() always redirects OAuth callbacks to the
            // central domain, so tenancy()->initialized is false in both subdomain
            // and path modes. The tenant routes (login & register) store the tenant's
            // UUID in the session before the Keycloak redirect, so $registeringTenantId
            // is the primary way to link new users regardless of tenancy mode.
            if (tenancy()->initialized) {
                $user->tenants()->attach(tenant('id'));
            } elseif ($registeringTenantId) {
                $user->tenants()->attach($registeringTenantId);
            }

            $isNew = true;
        } else {
            // Restore soft-deleted accounts that log in via Keycloak
            if ($user->trashed()) {
                $user->restore();
            }

            // Sync keycloak_id if the match was by email
            if ($user->keycloak_id === null) {
                $user->keycloak_id = $socialUser->getId();
            }

            // Keep name/email in sync with Keycloak
            $user->name = $socialUser->getName() ?: $user->name;
            $user->email_verified_at ??= now();
            $user->save();
        }

        Auth::login($user, remember: true);

        request()->session()->regenerate();

        // Store id_token for Keycloak single-logout and clear any stale back-channel flag.
        session(['keycloak_id_token' => $socialUser->accessTokenResponseBody['id_token'] ?? null]);

        if ($user->keycloak_id) {
            Cache::forget("keycloak_backchannel_logout:{$user->keycloak_id}");
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Redirect authenticated user to Keycloak to link their account.
     */
    public function linkRedirect(): RedirectResponse
    {
        $this->enforceCentralCallback('/auth/keycloak/link/callback');

        // prompt=login ensures Keycloak shows a fresh login even if a different
        // account is already signed in, preventing the "already authenticated
        // as different user" error.
        return Socialite::driver('keycloak') // @phpstan-ignore method.notFound
            ->with(['prompt' => 'login'])
            ->redirect();
    }

    /**
     * Handle the Keycloak callback for account linking.
     */
    public function linkCallback(): RedirectResponse
    {
        $this->enforceCentralCallback('/auth/keycloak/link/callback');

        try {
            $socialUser = Socialite::driver('keycloak')->user();
        } catch (\Throwable $e) {
            Log::error('Keycloak link callback failed', ['error' => $e->getMessage()]);

            return redirect()->route('profile.edit')->withErrors([
                'keycloak' => __('Could not link Keycloak account. Please try again.'),
            ]);
        }

        $conflict = User::where('keycloak_id', $socialUser->getId())
            ->where('id', '!=', Auth::id())
            ->first();

        if ($conflict !== null) {
            return redirect()->route('profile.edit')->withErrors([
                'keycloak' => __('This Keycloak account is already linked to another user.'),
            ]);
        }

        $user = Auth::user();
        $user->keycloak_id = $socialUser->getId();
        $user->save();

        return redirect()->route('profile.edit')->with('status', __('Keycloak account linked successfully.'));
    }

    /**
     * Unlink the Keycloak account from the current user.
     * Only allowed when the user has a local password set.
     */
    public function unlink(): RedirectResponse
    {
        $user = Auth::user();

        if ($user->password === null) {
            return redirect()->route('profile.edit')->withErrors([
                'keycloak' => __('Cannot unlink: no local password is set. You would be locked out.'),
            ]);
        }

        $user->keycloak_id = null;
        $user->save();

        return redirect()->route('profile.edit')->with('status', __('Keycloak account unlinked.'));
    }

    /**
     * Handle Keycloak OIDC back-channel logout.
     *
     * Keycloak POSTs a signed JWT logout_token when a session is terminated
     * externally (e.g. via the Keycloak admin console or another app).
     * We decode the token, extract the subject (keycloak_id), and set a short-
     * lived cache flag. The CheckKeycloakBackchannelLogout middleware then
     * intercepts the next request from that user and logs them out.
     */
    public function backchannelLogout(Request $request): Response
    {
        $logoutToken = $request->input('logout_token');

        if (! $logoutToken) {
            return response('Missing logout_token', 400);
        }

        $parts = explode('.', $logoutToken);

        if (count($parts) !== 3) {
            return response('Invalid logout_token format', 400);
        }

        $payload = json_decode(base64_decode(str_pad(
            str_replace(['-', '_'], ['+', '/'], $parts[1]),
            strlen($parts[1]) + (4 - strlen($parts[1]) % 4) % 4,
            '='
        )), true);

        if (! isset($payload['sub'])) {
            return response('Missing sub claim in logout_token', 400);
        }

        $keycloakId = $payload['sub'];

        Cache::put("keycloak_backchannel_logout:{$keycloakId}", true, now()->addMinutes(10));

        Log::info('Keycloak back-channel logout received', ['sub' => $keycloakId]);

        return response('', 200);
    }
}
