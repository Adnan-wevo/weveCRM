<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Redirect unauthenticated users to the correct login page.
     *
     * In subdomain/path tenancy mode, unauthenticated users on a tenant domain
     * must be redirected to the tenant login, not the central one.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            if (tenancy()->initialized) {
                return route('tenant.login', ['tenant' => $request->route('tenant') ?? tenant('id')]);
            }

            return route('login');
        }

        return null;
    }
}
