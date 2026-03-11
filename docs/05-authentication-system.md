# Authentication System

## Overview

The application implements four distinct authentication strategies, each serving different use cases:

| Strategy | Use Case | Backend |
|----------|----------|---------|
| Local credentials | Standard email/password login and registration | Laravel Fortify |
| Keycloak SSO | Enterprise single sign-on via OAuth2/OIDC | Laravel Socialite + custom provider |
| Magic Login | Passwordless email-link authentication | maize-tech/laravel-magic-login |
| API tokens | Machine-to-machine and SPA API access | Laravel Sanctum |

All strategies share the same `User` model and `web` guard. After authentication, the same `LoginResponse` logic determines where to redirect the user based on tenant associations.

---

## Local Authentication (Fortify)

### Configuration

Fortify is configured in `config/fortify.php` and `app/Providers/FortifyServiceProvider.php`.

**Enabled Features**:
- User registration
- Password reset
- Email verification
- Two-factor authentication (TOTP with confirmation and password confirmation)

**Views**: Disabled in the Fortify config (`'views' => false`). Views are registered manually in `FortifyServiceProvider` to support tenant-aware routing.

**Rate Limiting**: 5 attempts per minute for both login and two-factor challenge endpoints.

### Registration Flow

1. User navigates to `GET /register` (renders `livewire.auth.register` Blade view).
2. User submits the form to Fortify's `POST /register`.
3. `CreateNewUser` action validates input and creates the user.
4. If `registering_tenant_id` exists in the session (set when registering from a tenant context), the user is automatically associated with that tenant via the `tenant_user` pivot.
5. `LoginResponse` redirects to the appropriate dashboard.

### Login Flow

1. User navigates to `GET /login` (renders `livewire.auth.login`).
2. Credentials submitted to Fortify's `POST /login`.
3. On success, `LoginResponse` checks:
   - If the user is a non-super-admin with tenant associations, redirect to `tenant.dashboard`.
   - Otherwise, redirect to the central `dashboard`.
4. If 2FA is enabled, Fortify redirects to `GET /two-factor-challenge` before completing login.

### Password Reset Flow

1. User requests reset at `GET /forgot-password`.
2. Fortify sends a reset link to the email address.
3. User follows the link to `GET /reset-password/{token}`.
4. `ResetUserPassword` action validates and updates the password.

### Email Verification

After registration, unverified users are redirected to `GET /verify-email`. They can request a new verification email. The `verified` middleware blocks access to protected routes until verification is complete.

---

## Two-Factor Authentication

### Configuration

2FA is managed by Fortify's `TwoFactorAuthenticatable` trait on the `User` model. The feature is configured with:

- `confirm: true` -- Users must confirm 2FA setup with a valid TOTP code.
- `confirmPassword: true` -- Password confirmation is required before accessing 2FA settings.

### Setup Flow

The `TwoFactor` Livewire component (`app/Livewire/Settings/TwoFactor.php`) manages the 2FA lifecycle:

1. User navigates to `GET /settings/two-factor`.
2. **Enable**: Calls Fortify's `POST /user/two-factor-authentication`, which generates a TOTP secret and stores it encrypted. The QR code and manual setup key are displayed.
3. **Confirm**: User enters a 6-digit code from their authenticator app. Fortify validates against the secret via `POST /user/confirmed-two-factor-authentication`.
4. **Recovery Codes**: After confirmation, 8 recovery codes are generated and displayed. Users can regenerate codes at any time.
5. **Disable**: Calls Fortify's `DELETE /user/two-factor-authentication`.

### Challenge Flow

When a user with confirmed 2FA logs in:

1. Fortify suspends the login and redirects to `GET /two-factor-challenge`.
2. The view renders a Flux UI OTP input (`flux:otp`) for 6-digit code entry.
3. User can toggle to recovery code input mode.
4. On successful verification, `LoginResponse` completes the redirect.

### Database Columns

| Column | Type | Purpose |
|--------|------|---------|
| `two_factor_secret` | text (encrypted) | TOTP secret for code generation |
| `two_factor_recovery_codes` | text (encrypted) | JSON array of one-time recovery codes |
| `two_factor_confirmed_at` | timestamp | When 2FA was confirmed; null means pending |

---

## Keycloak SSO

### Architecture

Keycloak integration uses Laravel Socialite with a custom `KeycloakProvider` (`app/Socialite/KeycloakProvider.php`) that resolves networking challenges when the Keycloak server is outside the Docker Compose network (e.g. on the host machine or a remote server).

1. **Token Exchange via `CURLOPT_RESOLVE`**: The `getTokenUrl()` method builds the token URL using the **public** `KEYCLOAK_BASE_URL` (e.g. `https://localhost`) to keep the hostname, SNI, and `Host` header consistent with the SSL certificate. The `getHttpClient()` method uses `CURLOPT_RESOLVE` to transparently redirect DNS resolution of the public hostname to the IP address of `KEYCLOAK_INTERNAL_BASE_URL` (e.g. `https://host.docker.internal`). This means the HTTP request looks identical to a direct call to the public URL, but the TCP connection goes to the correct internal address. SSL verification is disabled only when the internal URL differs from the public URL, and HTTP/1.1 is forced to avoid protocol negotiation issues with reverse proxies.

2. **User Resolution**: The `getUserByToken()` method decodes the JWT access token payload directly instead of making an HTTP call to the userinfo endpoint, avoiding issuer mismatch errors when the internal and public hostnames differ.

#### Environment Variables

| Variable | Purpose | Example |
|----------|---------|--------|
| `KEYCLOAK_BASE_URL` | Browser-facing Keycloak URL (used for browser redirects, SNI, Host header) | `https://localhost` |
| `KEYCLOAK_INTERNAL_BASE_URL` | URL the app container uses to reach Keycloak (resolved via `CURLOPT_RESOLVE`) | `https://host.docker.internal` |
| `KEYCLOAK_REALM` | Keycloak realm name | `wevetel-platform` |
| `KEYCLOAK_CLIENT_ID` | OAuth client ID | `wevetel-1` |
| `KEYCLOAK_CLIENT_SECRET` | OAuth client secret | (from Keycloak admin) |
| `KEYCLOAK_REDIRECT_URI` | OAuth callback URL | `http://wevetel.test:8002/auth/keycloak/callback` |

When `KEYCLOAK_INTERNAL_BASE_URL` is empty or matches `KEYCLOAK_BASE_URL`, the provider behaves as a standard Socialite driver with no special networking.

### OAuth Flow

**Login** (`KeycloakController::redirect`):

1. Redirects to Keycloak's authorization endpoint with `prompt=login`.
2. The callback URL is forced to the central domain via `enforceCentralCallback()`.

**Callback** (`KeycloakController::callback`):

1. Exchanges the authorization code for tokens using the internal Keycloak URL.
2. Extracts user information from the JWT payload.
3. Looks up the user by `keycloak_id` or `email`.
4. If no user exists, creates one with a null password and the Keycloak ID.
5. If a matching email exists without a Keycloak ID, links the accounts.
6. Stores the `keycloak_id_token` in the session for logout.
7. If a `registering_tenant_id` session value exists, associates the user with that tenant and assigns the default `user` role.
8. Logs in and redirects via `LoginResponse`.

**Registration** (`KeycloakController::registerRedirect`):

1. Builds a redirect URL to Keycloak's dedicated `/protocol/openid-connect/registrations` endpoint.
2. Generates a random `state` parameter and stores it in the session for CSRF protection.
3. The browser is sent directly to the Keycloak registration form — no intermediate logout step is needed.
4. After registration, Keycloak redirects to the same `/auth/keycloak/callback` used by login.

> **Note**: The `/registrations` endpoint is used instead of `prompt=create` on the `/auth` endpoint because some Keycloak versions (notably 24.x) ignore `prompt=create` and display the login form instead. The `/registrations` endpoint reliably shows the registration form across all Keycloak versions.

The `registerFresh()` method is kept for backward compatibility but now delegates to `registerRedirect()`.

### Account Linking

**Link** (`KeycloakController::linkRedirect` / `linkCallback`):

1. Authenticated user navigates to `GET /auth/keycloak/link`.
2. Redirected to Keycloak authorization.
3. On callback, the Keycloak ID is attached to the existing user.
4. Conflict detection prevents linking if the Keycloak ID is already associated with a different user.

**Unlink** (`KeycloakController::unlink`):

1. User submits `DELETE /auth/keycloak/unlink`.
2. Requires the user to have a local password set (validated).
3. Removes the `keycloak_id` from the user record.

### Back-Channel Logout

Keycloak supports OIDC back-channel logout, which notifies the application when a user logs out of Keycloak:

1. Keycloak sends a `POST /auth/keycloak/backchannel-logout` with a signed JWT.
2. `KeycloakController::backchannelLogout()` decodes the JWT and extracts the `sub` (Keycloak user ID).
3. A cache flag `keycloak_backchannel_logout:{keycloak_id}` is set with a 1-hour TTL.
4. `CheckKeycloakBackchannelLogout` middleware (applied to all web requests) checks this flag on every request.
5. If the flag is found, the middleware logs the user out and redirects to login.

This endpoint is exempt from CSRF verification.

### Keycloak Setup (Automated)

The `keycloak-setup` Docker Compose service runs `docker/keycloak-setup.sh` automatically during `docker compose up -d`. It waits for Keycloak to be healthy, then:

1. Creates the realm (from `KEYCLOAK_REALM` in `.env`, default: `wevetel`) with self-registration and password reset enabled.
2. Creates a `laravel` confidential OAuth client with redirect URIs for both the central domain and wildcard subdomain patterns.
3. Configures back-channel logout URL to point to the internal Nginx service (`http://nginx:80/auth/keycloak/backchannel-logout`).
4. Retrieves the generated client secret and **automatically writes** `KEYCLOAK_CLIENT_ID` and `KEYCLOAK_CLIENT_SECRET` to the `.env` file.

The `app` container depends on `keycloak-setup` completing successfully, so the application always starts with the correct client secret — no manual `.env` editing is required.

> See [Docker Development Setup](13-docker-development-setup.md#keycloak-provisioning) for full technical details on the provisioning service, startup ordering, and troubleshooting.

---

## Magic Login (Passwordless)

### Configuration

Configured in `config/magic-login.php`:

| Setting | Value | Purpose |
|---------|-------|---------|
| `expiration` | 30 minutes | Link validity duration |
| `force_single` | true | Previous links are revoked when a new one is generated |
| `logins_limit` | 1 | Each link can be used exactly once |
| `redirect_url` | `/dashboard` | Default post-login redirect |
| `notification` | `MagicLinkNotification` | Custom notification class |

### Flow

1. User navigates to `GET /magic-login/request` (renders `MagicLogin` Livewire component).
2. User enters their email address and submits.
3. `MagicLink::send()` generates a signed URL and dispatches `MagicLinkNotification`.
4. The component always shows a success message regardless of whether the email exists (prevents user enumeration).
5. User clicks the link in the email, which authenticates them and redirects to the dashboard.

### Tenant Context

When magic login is initiated from a tenant route, the redirect URL is set to the tenant dashboard instead of the central dashboard.

### Notification

`MagicLinkNotification` extends `BaseMagicLinkNotification` and provides a branded email with:

- Greeting with the user's name.
- Explanation of link purpose and 30-minute expiry.
- "Sign in now" action button.
- Note that no further action is needed if the link was not requested.

---

## API Token Authentication (Sanctum)

### Token Lifecycle

**Issue Token** (`POST /api/v1/auth/token`):

Request body:
```json
{
    "email": "user@example.com",
    "password": "password",
    "device_name": "My Application"
}
```

Response:
```json
{
    "token": "1|abc123..."
}
```

The `device_name` field is optional and defaults to a standard label.

**Revoke Token** (`DELETE /api/v1/auth/token`):

Requires `Authorization: Bearer {token}` header. Deletes the current access token.

### Usage

Include the token in subsequent API requests:

```
Authorization: Bearer 1|abc123...
```

All module API routes (`/api/v1/access-control/*`) require `auth:sanctum` middleware.

---

## Post-Authentication Redirect Logic

The `LoginResponse` class determines the redirect destination after any authentication method:

1. If `TENANCY_MODE` is `single`, always redirect to the central dashboard.
2. If the user is a super-admin, redirect to the central dashboard.
3. If the user has tenant associations and is not a super-admin, redirect to the first tenant's dashboard.
4. Otherwise, redirect to the intended URL or the central dashboard.

The `LogoutResponse` class handles post-logout behavior:

1. Reads `_tenant_login` from the POST body for tenant-specific redirect.
2. If the user had a Keycloak session (ID token captured by middleware), redirects through Keycloak's end-session endpoint for full SSO logout.
3. For local-only users, redirects to the login page.

---

## Extending Authentication

### Adding a New OAuth Provider

1. Install the Socialite provider package.
2. Create a custom provider class in `app/Socialite/` if Docker networking adjustments are needed.
3. Register the provider in `AppServiceProvider`.
4. Create a controller similar to `KeycloakController`.
5. Add routes for redirect, callback, link, and unlink.
6. Add any required columns to the `users` migration (e.g., `provider_id`).

### Adding New Guards

1. Define the guard in `config/auth.php`.
2. If using Fortify, configure the guard in `config/fortify.php`.
3. Update middleware as needed.
