# Event, Job, and Queue System

## Queue Infrastructure

### Driver

The application uses Redis as the queue backend, configured in `config/queue.php`. All queued jobs are processed by Laravel Horizon.

### Horizon Configuration

Configured in `config/horizon.php`:

| Setting | Development | Production |
|---------|-------------|------------|
| Queue | `default` | `default` |
| Max Processes | 3 | 10 |
| Balance Strategy | -- | `auto` |
| Memory Limit | 128 MB | 128 MB |
| Timeout | 60 seconds | 60 seconds |
| Max Tries | 1 | 1 |

Horizon runs as a Supervisor-managed process inside the `app` container:

```ini
[program:horizon]
command=php /var/www/html/artisan horizon
user=www-data
autostart=true
autorestart=true
stopwaitsecs=3600
```

### Dashboard Access

Horizon's dashboard is accessible at `/horizon`. Authorization is controlled by `HorizonServiceProvider::gate()`, which is currently open (no email whitelist configured).

### Queue Connection

Redis is used for both the queue and the broadcasting pub/sub channel. The queue connection uses the `default` queue name.

## Broadcast Events

All broadcast events implement `ShouldBroadcastNow` (synchronous broadcasting, not queued). They broadcast on tenant-scoped public channels.

### Event Catalog

| Event | Module | Channel Pattern | Payload |
|-------|--------|-----------------|---------|
| `UserRecordChanged` | AccessControl | `access-control.users.{tenantId\|central}` | None (triggers table refresh) |
| `RoleRecordChanged` | AccessControl | `access-control.roles.{tenantId\|central}` | None (triggers table refresh) |
| `PermissionRecordChanged` | AccessControl | `access-control.permissions.{tenantId\|central}` | None (triggers table refresh) |
| `TenantRecordChanged` | OrganisationSetup | `organisation-setup.tenants.{tenantId\|central}` | None (triggers table refresh) |

### Channel Naming

Events auto-detect the tenant context:

- If tenancy is initialized: channel suffix is the current tenant's ID.
- If tenancy is not initialized: channel suffix is `central`.

Example: `access-control.users.550e8400-e29b-41d4-a716-446655440000` or `access-control.users.central`.

### Client-Side Subscription

The `resources/js/app.js` file subscribes to broadcast channels on Livewire initialization:

```javascript
window.Echo.channel(`access-control.users.${tenantId}`)
    .listen('UserRecordChanged', () => {
        Livewire.dispatch('refreshComponent');
    });
```

The `tenantId` variable is set from `window.wevetelTenantId`, which is exposed by the sidebar layout template.

## Queued Jobs

### Export Jobs

All export jobs share the same architectural pattern. The disk used is determined by `config('filesystems.exports_disk')`, set via `EXPORTS_DISK` in the environment.

| Job | Export Class | Output Path (tenant context) | Output Path (central) |
|-----|-------------|------------------------------|----------------------|
| `ExportPermissionsJob` | `PermissionsExport` | `{tenantId}/exports/access-control/permissions/{file}.xlsx` | `exports/access-control/permissions/{file}.xlsx` |
| `ExportRolesJob` | `RolesExport` | `{tenantId}/exports/access-control/roles/{file}.xlsx` | `exports/access-control/roles/{file}.xlsx` |
| `ExportUsersJob` | `UsersExport` | `{tenantId}/exports/access-control/users/{file}.xlsx` | `exports/access-control/users/{file}.xlsx` |
| `ExportTenantsJob` | `TenantsExport` | `{tenantId}/exports/organisation-setup/tenants/{file}.xlsx` | `exports/organisation-setup/tenants/{file}.xlsx` |

**Execution Pattern**:

1. Receive `$jobId`, `$userId`, and `$tenantId` (nullable) constructor parameters.
2. Build the file path: prepend `{tenantId}/` prefix when `$tenantId` is present.
3. Execute `Excel::store()` to write the XLSX file to `EXPORTS_DISK`.
4. Generate a signed 30-minute URL via `URL::temporarySignedRoute('secure.export', ...)`, including `?tenant={tenantId}` when applicable.
5. Cache the URL with `Cache::store(config('cache.default'))->put("export_job_{$jobId}", ...)` (bypasses `CacheTenancyBootstrapper` — see [07-multi-tenancy.md](07-multi-tenancy.md#cache-bootstrapper-caveat--horizon-queue-workers)).
6. On failure, cache the error message with the same key.

**Queue**: `default` (processed by Horizon).

### Import Jobs

| Job | Import Class | Source Path (tenant context) | Source Path (central) |
|-----|-------------|------------------------------|----------------------|
| `ImportPermissionsJob` | `PermissionsImport` | `{tenantId}/access-control/permissions/imports/{file}` | `access-control/permissions/imports/{file}` |
| `ImportRolesJob` | `RolesImport` | `{tenantId}/access-control/roles/imports/{file}` | `access-control/roles/imports/{file}` |
| `ImportUsersJob` | `UsersImport` | `{tenantId}/access-control/users/imports/{file}` | `access-control/users/imports/{file}` |
| `ImportTenantsJob` | `TenantsImport` | `{tenantId}/organisation-setup/tenants/imports/{file}` | `organisation-setup/tenants/imports/{file}` |

**Execution Pattern**:

1. Receive `$filePath` and `$jobId` parameters.
2. Execute `Excel::import()` reading from `EXPORTS_DISK` (the same disk the modal used to store the uploaded file).
3. Collect any row-level failures from `SkipsOnFailure`.
4. Cache the result summary with `Cache::store(config('cache.default'))->put("import_job_{$jobId}", ...)` (bypasses `CacheTenancyBootstrapper`).
5. On exception, cache the error message.

## Event Listeners

### SyncRolesPermissionsForNewTenant

**Listens to**: `Stancl\Tenancy\Events\TenantCreated`

**Action**: Invokes `SyncRolesPermissionsToTenantAction` to clone central role and permission templates to the newly created tenant.

**Detailed behavior**: See [10-service-layer.md](10-service-layer.md) for the sync process.

### Tenancy Event Wiring

`TenancyServiceProvider` registers the following event-listener mappings:

| Event | Listener(s) | Purpose |
|-------|-------------|---------|
| `TenantCreated` | `SyncRolesPermissionsForNewTenant` | Auto-provision tenant RBAC |
| `TenancyInitialized` | `BootstrapTenancy` | Initialize bootstrappers |
| `TenancyEnded` | `RevertToCentralContext` | Clean up tenant context |

Additional standard Stancl tenancy events are configured but use the default listeners for database management lifecycle (creating, updating, deleting databases -- though database-per-tenant is not active in this configuration).

## Scheduler

The Laravel scheduler is invoked by cron every minute inside the `app` container:

```
* * * * * www-data cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

No custom scheduled tasks are currently defined in `routes/console.php` beyond the default `inspire` command. Horizon's built-in cleanup and monitoring tasks run automatically.

## Job Failure Handling

Export and import jobs catch exceptions and cache the error message using `Cache::store(config('cache.default'))` to ensure the result is readable regardless of whether tenancy is initialized at read time (see [multi-tenancy docs](07-multi-tenancy.md#cache-bootstrapper-caveat--horizon-queue-workers)):

```php
public function failed(\Throwable $exception): void
{
    Cache::store(config('cache.default'))->put("export_job_{$this->jobId}", [
        'status'  => 'failed',
        'message' => $exception->getMessage(),
    ], now()->addMinutes(30));
}
```

The `ImportExportModal` Livewire component polls for the cache key every 3 seconds using the same store bypass:

```php
$result = Cache::store(config('cache.default'))->get("export_job_{$this->exportJobId}");
```

Failed jobs are also recorded in the `failed_jobs` table and visible in the Horizon dashboard.

## Creating New Events

To create a new broadcast event:

1. Create the event class implementing `ShouldBroadcastNow`.
2. Define the `broadcastOn()` method returning a tenant-scoped channel.
3. Dispatch the event from the relevant controller or Livewire component.
4. Subscribe to the channel in `resources/js/app.js`.
5. Handle the event in the Livewire component (e.g., refresh data).

## Creating New Jobs

1. Create the job class implementing `ShouldQueue`.
2. Define the `handle()` method with the job logic.
3. Implement `failed()` for error handling.
4. Dispatch from a controller or Livewire component.
5. Monitor through the Horizon dashboard.
