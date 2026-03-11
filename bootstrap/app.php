<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\CheckKeycloakBackchannelLogout::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'auth/keycloak/backchannel-logout',
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Unknown tenant in path mode → 404 instead of debug screen.
        $exceptions->render(function (\Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedByPathException $e, \Illuminate\Http\Request $request) {
            return response()->view('errors.tenant-not-found', [
                'tenantId' => last(explode(' ', $e->getMessage())),
            ], 404);
        });

        // Unknown domain in subdomain mode → 404 instead of debug screen.
        $exceptions->render(function (\Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException $e, \Illuminate\Http\Request $request) {
            return response()->view('errors.tenant-not-found', [
                'tenantId' => last(explode(' ', $e->getMessage())),
            ], 404);
        });

        // Unrecognised hosts (localhost, IPs, unregistered subdomains) that hit
        // Fortify's domain-unconstrained POST routes via GET get a 405.
        // Treat those as 404 — don't leak route method information.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, \Illuminate\Http\Request $request) {
            $host = $request->getHost();
            $centralDomains = config('tenancy.central_domains', []);

            if (! in_array($host, $centralDomains)) {
                return response()->view('errors.404', [], 404);
            }

            return null; // central domain — let the default 405 response through
        });
    })->create();
