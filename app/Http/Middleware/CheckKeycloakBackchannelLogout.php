<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckKeycloakBackchannelLogout
{
    /**
     * Check whether Keycloak has signalled a back-channel logout for the current
     * user. If a flag is found in the cache we invalidate the Laravel session so
     * the user is immediately signed out on their next request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Bind the id_token into the app container so LogoutResponse can read
        // it after Fortify has already invalidated the session.
        if ($token = $request->session()->get('keycloak_id_token')) {
            app()->instance('keycloak_id_token', $token);
        }

        $user = $request->user();

        if ($user && $user->keycloak_id) {
            $cacheKey = "keycloak_backchannel_logout:{$user->keycloak_id}";

            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);

                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}
