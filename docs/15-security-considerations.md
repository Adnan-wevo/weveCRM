# Security Considerations

## Overview

This document describes the security mechanisms implemented throughout the application, covering authentication, authorization, data protection, input validation, session management, CSRF protection, and tenant isolation.

## Authentication Security

### Password Policy

Laravel Fortify enforces the following password rules during registration and password update:

| Rule | Requirement |
|------|-------------|
| Minimum length | 12 characters |
| Mixed case | At least one uppercase and one lowercase letter |
| Numbers | At least one digit |
| Symbols | At least one special character |
| Compromised check | Password must not appear in known breach databases |

These rules are defined in `app/Actions/Fortify/PasswordValidationRules.php` using `Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()`.

### Two-Factor Authentication

Fortify provides TOTP-based two-factor authentication:

- Users enable 2FA from the settings page.
- QR code is generated for authenticator app enrollment.
- Recovery codes are generated and displayed once for offline backup.
- 2FA challenge is presented during login when enabled.
- A 6-digit OTP input (Flux `flux:otp`) validates the code.

### Magic Login Tokens

Passwordless login tokens:

- Generated with `grovyle/laravel-magic-login`.
- Tokens are single-use and time-limited.
- Delivered via email notification.
- Use signed URLs for tamper prevention.

### Keycloak SSO

- OAuth 2.0 Authorization Code flow with PKCE capability.
- Backchannel logout support for session invalidation.
- Dedicated route `/auth/keycloak/backchannel-logout` listens for Keycloak-initiated logouts.
- Docker networking uses an internal Keycloak URL (`http://keycloak:8080`) while the browser uses the public URL, handled by the custom `KeycloakProvider`.

### Session Security

Sessions are stored in the database by default (`SESSION_DRIVER=database`). Key configuration:

| Setting | Value |
|---------|-------|
| Driver | `database` |
| Lifetime | 120 minutes |
| Encryption | Configurable via `SESSION_ENCRYPT` |
| HTTP Only | `true` |
| Same-Site | `lax` |

### Sanctum API Tokens

- Tokens are hashed and stored in the `personal_access_tokens` table.
- Token abilities (scopes) can restrict API access.
- Token creation requires password confirmation.
- Tokens can be individually revoked.

## Authorization Security

### Permission Model

Spatie Laravel Permission provides role-based access control:

- 46+ granular permissions covering CRUD + import/export + sync operations.
- 3 default roles: `super-admin`, `admin`, `user`.
- Permissions are checked via middleware (`permission:`, `role:`) and Blade directives (`@can`, `@canany`).
- `super-admin` role bypasses all permission gates via `Gate::before()` in `AppServiceProvider`.

### Tenant-Scoped Authorization

- Roles and permissions have a `tenant_id` column.
- The `User` model overrides `hasPermissionTo()` and `hasRole()` to filter by current tenant context.
- Users can have different roles in different tenants.
- Tenant context is established by middleware before authorization checks run.

See [Authorization and Policies](06-authorization-and-policies.md) for the full permission matrix.

## CSRF Protection

All state-changing web requests are protected by Laravel's CSRF middleware:

- Automatic CSRF token injection via `@csrf` Blade directive.
- Livewire handles CSRF tokens transparently for all component interactions.
- API routes use Sanctum token authentication instead of CSRF tokens.

The custom `419.blade.php` error page provides a user-friendly message when a CSRF token expires.

## Input Validation

### Form Request Classes

All controller-based input passes through dedicated Form Request classes:

| Request Class | Validation Rules |
|---------------|-----------------|
| `StoreTokenRequest` | `token_name`: required, string, max 255 |

Module-level CRUD operations validate input within Livewire components using Livewire's built-in validation system.

### Livewire Component Validation

Livewire components define validation rules as properties or methods and call `$this->validate()` before persisting data. Example patterns:

- Required fields with type validation.
- Email uniqueness with tenant scoping.
- Enum validation for tenant modes.
- Array validation for role/permission assignment.

### Mass Assignment Protection

All Eloquent models use `$fillable` arrays to explicitly define which attributes can be mass-assigned. No models use `$guarded = []`.

## Tenant Data Isolation

### Query Scoping

The Tenancy package applies a global scope to tenant-aware models, ensuring that database queries are automatically filtered by the current tenant context. This prevents cross-tenant data leakage at the query level.

### Middleware Enforcement

Tenant identification and initialization occur in middleware before any controller or Livewire component executes. The middleware stack order ensures:

1. Tenant identification (from subdomain, path, or session).
2. Tenant initialization (bootstrappers: cache, filesystem, queue).
3. Authentication verification.
4. Authorization checks (tenant-scoped roles/permissions).

### Cache Key Prefixing

The `CacheTenancyBootstrapper` prefixes all cache keys with the tenant identifier, preventing cache collisions between tenants.

### Filesystem Isolation

The `FilesystemTenancyBootstrapper` scopes filesystem operations to tenant-specific directories, preventing file access across tenant boundaries.

### Queue Tenant Context

The `QueueTenancyBootstrapper` serializes tenant context into queued jobs, ensuring that background jobs execute within the correct tenant scope.

## File Security

### Secure Downloads

The `SecureDownloadController` generates signed URLs for file downloads:

- URLs are signed with an expiration time.
- Signature validation prevents URL tampering.
- Files are streamed via `Storage::download()`, not served directly from public directories.

### Media Library

Spatie Media Library uses:

- `RandomFileNamer` to prevent predictable file names.
- `AccessControlPathGenerator` to organize files by tenant and model.
- Private disk storage (not publicly accessible).

### Upload Limits

PHP configuration enforces upload size limits:

- Maximum upload: 50 MB.
- Maximum POST size: 50 MB.

## Debug Tool Access Control

### Telescope

Laravel Telescope is available at `/telescope`. Access is controlled by the `viewTelescope` gate defined in `app/Providers/TelescopeServiceProvider.php`:

- Only users with the `super-admin` Spatie role may access the dashboard.
- In non-local environments, Telescope only records reportable exceptions, failed requests, failed jobs, scheduled tasks, and monitored tags — not every request.
- Sensitive request parameters (`_token`) and headers (`cookie`, `x-csrf-token`, `x-xsrf-token`) are stripped from logged entries in non-local environments.
- `TELESCOPE_ENABLED=false` disables Telescope entirely (set in the test environment).

### Horizon

Laravel Horizon is available at `/horizon`. Access is controlled by the `viewHorizon` gate defined in `app/Providers/HorizonServiceProvider.php`:

- Only users with the `super-admin` Spatie role may access the dashboard.
- Unauthenticated requests are rejected — the gate callback requires a fully authenticated `User` instance.

Both gates use `$user->hasRole('super-admin')`, which is enforced against the central `super-admin` role (`tenant_id = null`).

## Rate Limiting

Laravel's built-in rate limiter is configured for authentication routes:

| Route | Limit |
|-------|-------|
| Login | Throttled by Fortify (`RateLimiter::for('login')`) |
| Two-Factor Challenge | Throttled by Fortify (`RateLimiter::for('two-factor')`) |
| API Routes | Throttled per Sanctum token |

## Error Handling

### Information Disclosure Prevention

Custom error pages replace default Laravel error pages:

- 403, 404, 419, 429, 500, 503 have branded error pages.
- Stack traces and debug information are not displayed in non-debug environments.
- `APP_DEBUG` should be `true` only in development.

### Exception Reporting

In `bootstrap/app.php`:

- `AuthenticationException` redirects to the login page.
- `TokenMismatchException` returns a 419 response with a user-friendly message.
- Generic exceptions render custom error views.

## Dependency Security

### Composer Audit

Run `composer audit` to check for known vulnerabilities in PHP dependencies.

### NPM Audit

Run `npm audit` to check for known vulnerabilities in JavaScript dependencies.

## Environment Variable Security

- Sensitive values (database passwords, API keys, secrets) are stored in `.env`, which is excluded from version control via `.gitignore`.
- The `env()` function is only used in configuration files, never in application code. Application code accesses configuration via `config()`.
- Reverb credentials are auto-generated by the entrypoint script if not present.

## Security Headers

Nginx configuration includes standard security-related headers. Additional headers can be added in `docker/nginx/default.conf` or via Laravel middleware.

## Recommendations for Template Users

1. **Change all default passwords** in `.env` before any non-local deployment.
2. **Enable session encryption** by setting `SESSION_ENCRYPT=true`.
3. **Set `APP_DEBUG=false`** in any shared environment.
4. **Rotate `APP_KEY`** after cloning the template.
5. **Configure Sanctum token expiration** for API security.
6. **Review Keycloak client settings** and restrict redirect URIs to actual domains.
7. **Enable HTTPS** when exposing the application beyond localhost.
8. **Run `composer audit` and `npm audit`** regularly.
9. **Verify `super-admin` role assignment** is restricted to trusted operations staff — this role grants access to Telescope, Horizon, and bypasses all permission gates.
