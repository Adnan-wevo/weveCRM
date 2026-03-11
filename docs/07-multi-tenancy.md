# Multi-Tenancy

## Architecture

This application implements multi-tenancy using `stancl/tenancy` (v3) with two database isolation strategies:

- **Single-Database** (default, `TENANCY_MULTI_DB=false`): All tenants share one MariaDB database. Tenant isolation is enforced at the application level through `tenant_id` columns, scoping middleware, and tenant-aware query methods.
- **Multi-Database** (`TENANCY_MULTI_DB=true`): Each tenant gets its own database, automatically created/migrated/deleted via stancl job pipelines. See [docs/25-multi-database-tenancy.md](25-multi-database-tenancy.md) for the full multi-database guide.

Active bootstrappers handle cache prefixing, filesystem paths, queue tenant tagging, and (when multi-db is enabled) database connection switching.

## Tenancy Modes

The tenancy mode is controlled by the `TENANCY_MODE` environment variable:

| Mode | URL Pattern | Resolution Middleware | Description |
|------|-------------|----------------------|-------------|
| `single` | `yourdomain.com/*` | None | Tenancy disabled; application operates as a single-tenant system |
| `subdomain` | `{tenant}.yourdomain.com/*` | `InitializeTenancyByDomain` | Tenant resolved from the subdomain |
| `path` | `yourdomain.com/{tenant}/*` | `InitializeTenancyByPath` | Tenant resolved from the first URL path segment |

### Single Mode

When `TENANCY_MODE=single`:

- `TenancyServiceProvider::boot()` returns early; no tenancy events, routes, or middleware are registered.
- `EnsureCentralAccess` and `EnsureTenantAccess` middleware are no-ops.
- All routes are central routes.
- Roles and permissions exist without `tenant_id` scoping.

### Subdomain Mode

When `TENANCY_MODE=subdomain`:

- `TENANCY_CENTRAL_DOMAINS` defines which domains serve central content (e.g., `wevetel.test`).
- Tenant subdomains are registered in the `domains` table (e.g., `acme.wevetel.test`).
- Central routes are constrained to central domains.
- Tenant routes use `InitializeTenancyByDomain` and `PreventAccessFromCentralDomains`.
- `SESSION_DOMAIN` must be set with a leading dot (e.g., `.wevetel.test`) so the session cookie is shared across all subdomains. Without this, users will lose their session when navigating between the central domain and tenant subdomains.

```env
# Required for subdomain mode — leading dot shares cookies across subdomains
SESSION_DOMAIN=.wevetel.test
```

### Path Mode

When `TENANCY_MODE=path`:

- All routes share the same domain.
- Tenant routes are prefixed with `/{tenant}` (e.g., `/acme/dashboard`).
- `InitializeTenancyByPath` extracts the tenant ID from the URL.
- `InitializeTenancyFromLivewireUpdate` middleware handles path-mode tenancy for Livewire AJAX requests by extracting the tenant from the `Referer` header.

## Configuration

Key settings in `config/tenancy.php`:

| Setting | Value | Purpose |
|---------|-------|---------|
| `tenancy.mode` | `env('TENANCY_MODE', 'single')` | Active tenancy mode |
| `tenancy.central_domains` | From `TENANCY_CENTRAL_DOMAINS` env | Domains that serve central content |
| `tenancy.tenant_model` | `App\Models\Tenant` | Tenant Eloquent model |
| `tenancy.id_generator` | `Stancl\Tenancy\UUIDGenerator` | UUID-based tenant IDs |
| `tenancy.bootstrappers` | Cache, Filesystem, Queue (+ Database when multi-db) | Active isolation bootstrappers |

### Bootstrappers

| Bootstrapper | Purpose |
|--------------|---------|
| `CacheTenancyBootstrapper` | Prefixes cache keys with tenant ID to prevent cross-tenant cache collisions |
| `FilesystemTenancyBootstrapper` | Scopes filesystem paths to tenant-specific directories |
| `QueueTenancyBootstrapper` | Tags queued jobs with tenant context for proper execution |

The `DatabaseTenancyBootstrapper` is conditionally enabled based on `TENANCY_MULTI_DB`. When disabled (default), query scoping is handled at the model level via `BelongsToTenant`.
### Cache Bootstrapper Caveat — Horizon Queue Workers

`CacheTenancyBootstrapper` wraps all `Cache::` calls with tenant-specific tags when tenancy is initialized. However, Horizon processes queued jobs in a fresh process that re-initializes tenant context from the job payload's `tenant_id` field (via `QueueTenancyBootstrapper`). In practice, due to how Horizon v5 bootstraps, the tenant context may **not** be active when the job's `handle()` method runs.

**Consequence**: If a job calls `Cache::put('my_key', ...)` and a subsequent Livewire component calls `Cache::get('my_key')` from a tenant context, the get will apply a tenant tag that the put never applied, returning `null`.

**Fix**: Use `Cache::store(config('cache.default'))` to call the underlying cache store directly, bypassing the `TenantCacheManager` wrapper entirely. This makes cache writes and reads consistent regardless of whether tenancy is initialized:

```php
// In queued jobs and Livewire components — use store() to bypass tenant tagging
Cache::store(config('cache.default'))->put($key, $value, $ttl);
Cache::store(config('cache.default'))->get($key);
```

This pattern is used throughout the export and import jobs and their corresponding `ImportExportModal` Livewire components.
## Tenant Model

`App\Models\Tenant` extends `Stancl\Tenancy\Database\Models\Tenant`:

- Primary key: string (UUID, generated by `UUIDGenerator`).
- JSON `data` column stores arbitrary tenant attributes (name, plan, settings).
- `HasDomains` trait provides the `domains()` relationship for subdomain mode.
- `HasDatabase` trait and `TenantWithDatabase` interface enable multi-database support.
- `SoftDeletes` enables soft deletion.
- `Auditable` tracks all changes.
- `InteractsWithMedia` supports tenant-level media attachments.

**Relationships**:

| Relationship | Type | Via |
|-------------|------|-----|
| `users()` | BelongsToMany | `tenant_user` pivot table |
| `domains()` | HasMany | `domains` table (provided by `HasDomains`) |

## Data Scoping

### BaseModel Tenant Scoping

The abstract `BaseModel` includes the `BelongsToTenant` trait from stancl/tenancy. When tenancy is initialized, all queries on models extending `BaseModel` are automatically scoped to the current tenant's `tenant_id`.

### Role and Permission Scoping

`Role` and `Permission` models do not extend `BaseModel`. They implement tenant scoping manually through:

- A `tenant_id` column that is part of the unique constraint with `name` and `guard_name`.
- `scopeCentral()` and `scopeForTenant($id)` query scopes.
- The `User` model's overridden `hasPermissionTo()` and `getPermissionsViaRoles()` methods, which filter by the current tenant context.

### User Model

`User` does not extend `BaseModel` and does not have a `tenant_id` column. Users are associated with tenants through the `tenant_user` many-to-many pivot table, allowing a single user to belong to multiple tenants.

## Tenant Lifecycle

### Creation

1. A new tenant is created via the `CreateModal` Livewire component or API.
2. The `TenantCreated` event fires.
3. `SyncRolesPermissionsForNewTenant` listener invokes `SyncRolesPermissionsToTenantAction`.
4. Central roles and permissions are cloned to the new tenant (see [06-authorization-and-policies.md](06-authorization-and-policies.md)).

### Domain Assignment (Subdomain Mode)

Domains are managed through the `AssignDomainModal` Livewire component in the OrganisationSetup module. Each domain record maps a subdomain to a tenant.

### User Association

Users are added to tenants through the `TenantUserModal` component:

1. Enter the user's email address.
2. Optionally assign a tenant-scoped role.
3. The user is attached to the tenant via the `tenant_user` pivot.

Users can be removed from tenants without deleting their accounts.

## Route Isolation

### Central Routes

Central routes are defined in `routes/web.php` and module web routes. In subdomain mode, they are constrained to the central domain(s). The `EnsureCentralAccess` middleware:

1. Checks if the current user has any tenant associations.
2. If the user is not a super-admin and has tenants, redirects to their first tenant's dashboard.
3. No-op in single-tenant mode.

### Tenant Routes

Tenant routes are defined in `routes/tenant.php` and mirror the central route structure with the `tenant.` name prefix. The `EnsureTenantAccess` middleware:

1. Redirects super-admins to the central dashboard.
2. Checks if the authenticated user belongs to the current tenant.
3. If not, redirects to the user's own tenant dashboard or the home page.
4. No-op in single-tenant mode.

### Tenant Route Parameter Resolution

The `ResolveTenantRouteParam` trait provides:

- `resolveTenantRouteParam()` -- Returns the correct route parameter value:
  - Subdomain mode: extracts the subdomain slug from the tenant's first domain record.
  - Path mode: uses the tenant's ID directly.
- `isSingleTenantMode()` -- Returns `true` when the `tenant.*` route names do not exist.

## Middleware Configuration

### Middleware Priority

`TenancyServiceProvider` prepends tenancy middleware to the global priority list, ensuring tenant initialization occurs before any other middleware:

```
InitializeTenancyByDomain (or InitializeTenancyByPath)
PreventAccessFromCentralDomains
```

### Livewire Persistent Middleware

In multi-tenant modes, the tenancy initialization middleware is registered as Livewire persistent middleware:

- Subdomain mode: `InitializeTenancyByDomain`, `PreventAccessFromCentralDomains`
- Path mode: `InitializeTenancyByPath`

This ensures that Livewire component updates maintain the correct tenant context.

### Livewire Update Handling (Path Mode)

The `InitializeTenancyFromLivewireUpdate` middleware handles a specific challenge in path mode: Livewire's `/livewire/update` endpoint does not include the tenant path prefix. This middleware:

1. Checks if the request is a Livewire update and tenancy mode is `path`.
2. Extracts the tenant ID from the `Referer` header's first path segment.
3. Initializes tenancy with the extracted tenant ID.

## Authentication in Tenant Context

### Login Redirect

When a non-super-admin user with tenant associations logs in, `LoginResponse` redirects them to their first tenant's dashboard. The route name depends on the tenancy mode:

- Subdomain: `tenant.dashboard` with the subdomain parameter.
- Path: `tenant.dashboard` with the tenant ID parameter.

### Registration from Tenant

When a user registers from a tenant URL, `routes/tenant.php` sets `registering_tenant_id` in the session. The `CreateNewUser` action checks for this value and automatically associates the new user with the tenant.

### Keycloak SSO in Tenant Context

When Keycloak OAuth is initiated from a tenant context, `KeycloakController::callback()`:

1. Reads `registering_tenant_id` from the session.
2. Associates the user with the tenant if not already associated.
3. Assigns the default `user` role within the tenant context.

## Extending Tenancy

### Adding Tenant-Scoped Models

1. Create model extending `BaseModel` for automatic `tenant_id` scoping and all standard traits.
2. Include `tenant_id` as a nullable string column (indexed) in the migration.
3. Place tenant-specific migrations in `database/migrations/tenant/`.
4. In single-db mode, `TenancyServiceProvider` auto-loads tenant migrations into the central database.
5. In multi-db mode, `MigrateDatabase` job runs them against each tenant's own database.
6. See [docs/25-multi-database-tenancy.md](25-multi-database-tenancy.md) for the complete guide.

### Adding Tenant Routes

1. Define routes in `routes/tenant.php` within the appropriate mode block (subdomain or path).
2. Apply `EnsureTenantAccess` middleware.
3. Use the `tenant.` route name prefix.
4. Mirror the route structure in the central context with `EnsureCentralAccess` if the feature should also be available centrally.

### Custom Tenant Data

The `Tenant` model stores arbitrary data in its JSON `data` column. Access custom attributes directly:

```php
$tenant->name;
$tenant->plan;
$tenant->settings;
```

These attributes are automatically serialized and deserialized by the Stancl base model.
