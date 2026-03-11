# Service Layer

## Overview

The application uses a lightweight service layer composed of Actions, Support utilities, and Concerns (traits). There is no formal service class layer; business logic is distributed across Fortify Actions, domain-specific Actions, and reusable Support classes.

## Actions

### Fortify Actions (`app/Actions/Fortify/`)

These actions implement Fortify's contract interfaces for authentication workflows:

#### CreateNewUser

Implements `CreatesNewUsers`.

**Responsibilities**:

1. Validates name, email, and password using `ProfileValidationRules` and `PasswordValidationRules` traits.
2. Creates the user with a hashed password.
3. Checks the session for `registering_tenant_id` (set when registering from a tenant context).
4. If present, attaches the user to the specified tenant via the `tenant_user` pivot.

**Tenant Context**: When a user registers from a tenant URL, the tenant route sets `registering_tenant_id` in the session before Fortify processes the registration. This ensures the user is automatically associated with the correct tenant without requiring manual intervention.

#### ResetUserPassword

Implements `ResetsUserPasswords`.

**Responsibilities**:

1. Validates the new password using `PasswordValidationRules`.
2. Force-fills the password on the user model (uses `forceFill` to bypass mass-assignment protection on the `password` field's hashed cast).

### Domain Actions (`app/Actions/`)

#### SyncRolesPermissionsToTenantAction

Invokable action class that synchronizes central role and permission templates to a specific tenant.

**Invocation**: Called by `SyncRolesPermissionsForNewTenant` listener on `TenantCreated` event.

**Process**:

1. Skips execution if `TENANCY_MODE` is `single`.
2. Loads all central permissions (`tenant_id = null`).
3. Clones each permission to the tenant using upsert (matched by `name` and `guard_name`), setting `is_synced = true`.
4. Excludes write permissions for the permissions module. Tenants receive only `access-control.permissions.index` and `access-control.permissions.show`.
5. Loads the central `admin` and `user` roles (not `super-admin`).
6. Creates matching tenant roles via upsert.
7. For each tenant role, resolves the corresponding permissions from the tenant's permission set and syncs them.
8. Clears the Spatie permission cache to ensure the new entries take effect immediately.

**Idempotency**: Uses upsert operations, so running the sync multiple times does not create duplicate records.

## Support Utilities (`app/Support/`)

### RecordLock

Cache-based optimistic record locking system that prevents concurrent editing of the same record by multiple users.

**TTL**: 5 minutes (automatically expires).

**Cache Key Pattern**: `record_lock:{model}:{id}` (e.g., `record_lock:users:uuid-here`).

**Methods**:

| Method | Parameters | Returns | Purpose |
|--------|-----------|---------|---------|
| `acquire` | `$model, $id, $userId, $userName` | `bool` | Acquire or extend a lock |
| `release` | `$model, $id, $userId` | `bool` | Release a lock if owned by the specified user |
| `isLockedByOther` | `$model, $id, $userId` | `bool` | Check if another user holds the lock |
| `lockedBy` | `$model, $id` | `?array` | Get lock details (user_id, user_name, locked_at) |
| `getLockedByOthers` | `$model, $ids, $userId` | `array` | Batch check for locks on multiple records |

**Usage in Livewire Components**: Edit modals check `$this->lockedIds` before opening. The Index component calls `getLockedByOthers()` to display lock indicators in the data table. When a user opens an edit modal, the lock is acquired. When the modal closes, the lock is released.

**Lock Data Structure**:

```php
[
    'user_id' => 'uuid',
    'user_name' => 'Jane Smith',
    'locked_at' => '2026-02-21T10:00:00Z'
]
```

### RandomFileNamer (`app/Support/Media/`)

Custom file namer for Spatie Media Library. Generates 32-character random hexadecimal strings as filenames, preventing filename enumeration and conflicts.

Configured in `config/media-library.php`:

```php
'file_namer' => App\Support\Media\RandomFileNamer::class,
```

### AccessControlPathGenerator (`Modules/AccessControl/app/Support/Media/`)

Custom path generator for Spatie Media Library. Generates storage paths in the format:

```
access-control/{collection_name}/{model_id}/
```

Configured in `config/media-library.php`:

```php
'path_generator' => Modules\AccessControl\Support\Media\AccessControlPathGenerator::class,
```

## Concerns (Traits)

### PasswordValidationRules (`app/Concerns/`)

Provides reusable password validation methods:

| Method | Rules |
|--------|-------|
| `passwordRules()` | `required`, `string`, `Password::default()`, `confirmed` |
| `currentPasswordRules()` | `required`, `string`, `current_password` |

`Password::default()` is configured in `AppServiceProvider` to require 12+ characters with mixed case, numbers, symbols, and uncompromised check (production only).

### ProfileValidationRules (`app/Concerns/`)

Provides reusable profile field validation methods:

| Method | Parameters | Rules |
|--------|-----------|-------|
| `nameRules()` | -- | `required`, `string`, `max:255` |
| `emailRules($userId)` | Optional user ID to ignore in unique check | `required`, `string`, `email`, `max:255`, `unique:users` |

### ResolveTenantRouteParam (`app/Concerns/`)

Resolves the `{tenant}` route parameter for generating tenant-aware URLs across tenancy modes:

| Mode | Resolution |
|------|-----------|
| Subdomain | Extracts subdomain slug from the tenant's first domain record |
| Path | Uses the tenant's ID directly |
| Single | Returns null (tenant routes do not exist) |

**Methods**:

| Method | Returns | Purpose |
|--------|---------|---------|
| `resolveTenantRouteParam()` | `?string` | Get the current tenant's route parameter value |
| `isSingleTenantMode()` | `bool` | Check if tenant routes are registered |

Used by `EnsureCentralAccess`, `EnsureTenantAccess`, and Livewire components to generate correct route URLs regardless of tenancy mode.

## Import/Export Pipeline

### Export Flow

1. User triggers export from `ImportExportModal`.
2. A unique `jobId` is generated.
3. The appropriate export job is dispatched to the queue (e.g., `ExportUsersJob`).
4. The job executes `Excel::store()` with the export class to the configured disk.
5. On success, a signed download URL (30-minute TTL) is cached with key `export_job_{$jobId}`.
6. On failure, the error message is cached with the same key.
7. The modal polls (`wire:poll.3000ms`) for the cache key.
8. When found, the download link is presented or the error is displayed.

### Import Flow

1. User uploads a file (CSV or XLSX) in the `ImportExportModal`.
2. The file is validated for correct headings.
3. A preview of the first rows is displayed for confirmation.
4. On confirmation, the import job is dispatched (e.g., `ImportUsersJob`).
5. The job executes `Excel::import()` from the local disk.
6. Results (including failure count and details) are cached with key `import_job_{$jobId}`.
7. The modal polls for results and displays the outcome.

### Export Classes

All export classes follow the same pattern:

```
FromQuery       -- Provides the Eloquent query
WithHeadings    -- Column header row
WithMapping     -- Row data transformation
ShouldAutoSize  -- Auto-size columns
```

### Import Classes

All import classes follow the same pattern:

```
ToModel         -- Creates one model per row
WithHeadingRow  -- Uses first row as field names
WithValidation  -- Row-level validation rules
SkipsOnFailure  -- Collects failures instead of aborting
```

Each import class provides:

- `getFailures()` -- Returns collected import failures.
- `validateRow($row)` -- Pre-import row validation.
- `headings()` -- Static method returning expected column names.

## Custom Login/Logout Responses

### LoginResponse (`app/Http/Responses/`)

Implements `LoginResponseContract`. Post-authentication redirect logic:

1. Single-tenant mode: Always redirect to `route('dashboard')`.
2. User is super-admin: Redirect to `route('dashboard')`.
3. User has tenant associations: Redirect to `route('tenant.dashboard')` with resolved tenant parameter.
4. Default: `redirect()->intended(route('dashboard'))`.

### LogoutResponse (`app/Http/Responses/`)

Implements `LogoutResponseContract`. Post-logout redirect logic:

1. Reads `_tenant_login` from the POST body for tenant-specific login URL.
2. Checks if a Keycloak ID token was captured during the session.
3. If Keycloak token exists: Redirects through Keycloak's `end-session` endpoint with `post_logout_redirect_uri` pointing to the appropriate login page.
4. Otherwise: Redirects to the login URL (tenant-specific or central).

## Custom Socialite Provider

### KeycloakProvider (`app/Socialite/`)

Extends `SocialiteProviders\Keycloak\Provider` with two Docker-specific overrides:

1. **`getTokenUrl()`**: Returns `KEYCLOAK_INTERNAL_BASE_URL` (e.g., `http://keycloak:8080`) for the token exchange URL. The default provider would use the browser-facing URL, which the PHP container cannot reach.

2. **`getUserByToken($token)`**: Decodes the JWT access token payload directly using `base64_decode` instead of making an HTTP call to the Keycloak userinfo endpoint. This avoids issuer mismatch errors that occur when the JWT issuer (browser-facing URL) differs from the URL the container uses to call Keycloak.
