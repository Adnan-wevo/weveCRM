# Request Lifecycle

## Overview

Every HTTP request follows a deterministic path through the Laravel application, from Nginx ingress through the middleware pipeline to response rendering. This document traces that path and explains each stage.

## Entry Point

All HTTP requests enter through `public/index.php`, which bootstraps the application via `bootstrap/app.php`.

### Bootstrap Configuration

`bootstrap/app.php` configures:

- **Routing**: Loads `routes/web.php`, `routes/api.php`, `routes/console.php`, and `routes/channels.php`. Tenant routes (`routes/tenant.php`) are loaded separately by `TenancyServiceProvider`.
- **Middleware**: Configures the web middleware group, CSRF exceptions, and middleware aliases.
- **Exceptions**: Custom exception rendering for tenant identification failures and method-not-allowed errors on non-central domains.

### Provider Boot Sequence

Providers registered in `bootstrap/providers.php` boot in the following order:

| Order | Provider | Boot Responsibilities |
|-------|----------|----------------------|
| 1 | `AppServiceProvider` | Keycloak Socialite driver, Gate super-admin bypass, password defaults, Tailwind pagination, CarbonImmutable |
| 2 | `FortifyServiceProvider` | Custom login/logout responses, auth actions, Fortify view registration, rate limiting |
| 3 | `HorizonServiceProvider` | Horizon authorization gate |
| 4 | `TelescopeServiceProvider` | Telescope entry filtering, sensitive data hiding |
| 5 | `TenancyServiceProvider` | Tenancy events, tenant route mapping, middleware priority, Livewire persistent middleware |

Module service providers (`AccessControlServiceProvider`, `OrganisationSetupServiceProvider`) are discovered automatically via `nwidart/laravel-modules`.

## Middleware Pipeline

### Web Middleware Group

Requests to web routes pass through the default Laravel web middleware group with one addition:

```
StartSession
ShareErrorsFromSession
VerifyCsrfToken (except: auth/keycloak/backchannel-logout)
SubstituteBindings
CheckKeycloakBackchannelLogout  <-- appended
```

`CheckKeycloakBackchannelLogout` executes on every web request:

1. Captures the `keycloak_id_token` from the session into the application container.
2. If the authenticated user has a `keycloak_id`, checks the cache for a back-channel logout flag.
3. If the flag exists, logs out the user, invalidates the session, and redirects to login.

### Middleware Aliases

| Alias | Class | Purpose |
|-------|-------|---------|
| `auth` | `App\Http\Middleware\Authenticate` | Authentication with tenant-aware redirect |
| `role` | `Spatie\Permission\Middleware\RoleMiddleware` | Role-based access |
| `permission` | `Spatie\Permission\Middleware\PermissionMiddleware` | Permission-based access |
| `role_or_permission` | `Spatie\Permission\Middleware\RoleOrPermissionMiddleware` | Combined check |

### Tenancy Middleware

When tenancy is active (mode is not `single`), the `TenancyServiceProvider` prepends tenancy initialization middleware to the global priority list:

- `InitializeTenancyByDomain` (subdomain mode)
- `InitializeTenancyByPath` (path mode)
- `PreventAccessFromCentralDomains`

These are applied to tenant route groups in `routes/tenant.php`.

### Access Control Middleware

Two custom middleware enforce route-level tenant isolation:

| Middleware | Applied To | Behavior |
|------------|-----------|----------|
| `EnsureCentralAccess` | Central routes (dashboard, settings, modules) | Redirects non-super-admin tenant users to their tenant dashboard |
| `EnsureTenantAccess` | Tenant routes | Redirects super-admins to central; blocks users not belonging to the current tenant |

Both middleware are no-ops in single-tenant mode.

### Livewire Middleware

In path-mode tenancy, `InitializeTenancyFromLivewireUpdate` handles the `/livewire/update` AJAX endpoint by extracting the tenant ID from the `Referer` header and initializing tenancy before the Livewire component processes.

## Route Resolution

### Central Routes (`routes/web.php`)

Central routes serve the primary application context. In subdomain mode, they are constrained to the configured central domain(s).

```
/                           Welcome page
/login                      Login form (GET, Fortify handles POST)
/register                   Registration form
/forgot-password            Password reset request
/reset-password/{token}     Password reset form
/verify-email               Email verification notice
/confirm-password           Password confirmation
/two-factor-challenge       2FA OTP entry
/dashboard                  Authenticated dashboard
/settings/*                 Profile, password, appearance, 2FA
/auth/keycloak/*            Keycloak SSO flow
/magic-login/*              Passwordless authentication
/secure/exports/*           Signed export downloads
/secure/media/*             Signed media downloads
/access-control/*           Users, roles, permissions (module)
/organisation-setup/*       Tenants (module)
```

### Tenant Routes (`routes/tenant.php`)

Tenant routes replicate the central route structure under tenant context:

**Subdomain mode**: `{tenant}.yourdomain.com/dashboard`

**Path mode**: `yourdomain.com/{tenant}/dashboard`

Named routes use the `tenant.` prefix (e.g., `tenant.dashboard`, `tenant.login`).

### API Routes (`routes/api.php`)

API routes are prefixed with `/api` and use the `api` middleware group:

```
POST   /api/v1/auth/token      Issue Sanctum token
DELETE /api/v1/auth/token      Revoke current token
```

Module API routes extend the v1 namespace:

```
/api/v1/access-control/users/*
/api/v1/access-control/roles/*
/api/v1/access-control/permissions/*
```

## Authentication Flow

The request lifecycle branches based on the authentication strategy:

### Local Authentication (Fortify)

1. User submits credentials to Fortify's `POST /login`.
2. Fortify authenticates via the `web` guard.
3. Custom `LoginResponse` checks the user's tenant associations.
4. Non-super-admin users with a tenant are redirected to `tenant.dashboard`.
5. All others redirect to the central `dashboard`.

### Keycloak SSO

1. User clicks the Keycloak login button, redirected to `GET /auth/keycloak/redirect`.
2. `KeycloakController` redirects to the Keycloak authorization endpoint.
3. Keycloak returns to `GET /auth/keycloak/callback`.
4. Controller finds or creates the user, links tenant if applicable, and logs in.
5. Custom `LoginResponse` handles the redirect.

### API Authentication (Sanctum)

1. Client sends `POST /api/v1/auth/token` with credentials.
2. `TokenController` validates and returns a Bearer token.
3. Subsequent requests include `Authorization: Bearer {token}`.
4. `auth:sanctum` middleware authenticates the token.

## Response Rendering

### Livewire Full-Page Components

Most views render as Livewire full-page components. The component's `render()` method returns a Blade view, which Livewire wraps in the specified layout (`layouts.app` or `layouts.auth`).

### Blade Templates

Standard Blade views are used for the welcome page, error pages, and simple static content. Layouts use Flux UI components for consistent styling.

### API Responses

API controllers return Eloquent API Resources (`UserResource`, `RoleResource`, `PermissionResource`) which format model data as JSON with ISO 8601 timestamps and conditional relationship inclusion.

## Exception Handling

Custom exception rendering is configured in `bootstrap/app.php`:

| Exception | Response |
|-----------|----------|
| `TenantCouldNotBeIdentifiedByPathException` | 404 with `errors.tenant-not-found` view |
| `TenantCouldNotBeIdentifiedOnDomainException` | 404 with `errors.tenant-not-found` view |
| `MethodNotAllowedHttpException` on non-central domains | 404 (prevents route method enumeration) |

## Logout Flow

The `LogoutResponse` and `Logout` Livewire action handle session termination:

1. Capture the `keycloak_id_token` from the session before destruction.
2. Call `Auth::guard('web')->logout()`.
3. Invalidate and regenerate the session.
4. If a Keycloak token exists, redirect through the Keycloak end-session endpoint for full SSO logout.
5. Otherwise, redirect to the login page.
