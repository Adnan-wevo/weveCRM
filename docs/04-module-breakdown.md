# Module Breakdown

## Module Architecture

This application uses `nwidart/laravel-modules` (v12) to organize domain-specific functionality into self-contained modules. Each module operates as a semi-independent package with its own controllers, Livewire components, events, jobs, views, routes, and service providers.

### Module Registration

Modules are auto-discovered from the `Modules/` directory. The `modules_statuses.json` file controls which modules are active:

```json
{
    "AccessControl": true,
    "OrganisationSetup": true
}
```

Composer's merge plugin includes each module's `composer.json` for autoloading:

```json
"extra": {
    "merge-plugin": {
        "include": ["Modules/*/composer.json"]
    }
}
```

### Module Structure Convention

Each module follows a consistent internal structure:

```
Modules/{ModuleName}/
|-- app/
|   |-- Events/           Broadcast events for real-time updates
|   |-- Exports/           Excel export query classes
|   |-- Http/
|   |   |-- Controllers/   API controllers
|   |   |-- Requests/      Form request validation
|   |   |-- Resources/     API resource transformers
|   |-- Imports/           Excel import handlers
|   |-- Jobs/              Queued background jobs
|   |-- Livewire/          Livewire UI components
|   |-- Providers/         Service, event, and route providers
|   |-- Support/           Module-specific utilities
|-- config/                Module configuration
|-- resources/views/       Blade templates
|-- routes/
|   |-- api.php            API route definitions
|   |-- web.php            Web route definitions
```

---

## AccessControl Module

### Purpose

Manages the lifecycle of users, roles, and permissions. Provides both a Livewire-based web interface and a RESTful API for all CRUD operations, including soft-delete, restore, force-delete, bulk operations, import/export, and audit history.

### Route Configuration

**Web Routes** (`Modules/AccessControl/routes/web.php`):

```
/access-control/users          Users management page
/access-control/roles          Roles management page
/access-control/permissions    Permissions management page
```

All web routes require `auth`, `verified`, and `EnsureCentralAccess` middleware. In subdomain tenancy mode, routes are constrained to the central domain.

**API Routes** (`Modules/AccessControl/routes/api.php`):

```
GET    /api/v1/access-control/users                  List users
POST   /api/v1/access-control/users                  Create user
GET    /api/v1/access-control/users/{user}            Show user
PUT    /api/v1/access-control/users/{user}            Update user
DELETE /api/v1/access-control/users/{user}            Soft-delete user
POST   /api/v1/access-control/users/{user}/restore    Restore user
DELETE /api/v1/access-control/users/{user}/force       Force-delete user

GET    /api/v1/access-control/roles                   List roles
POST   /api/v1/access-control/roles                   Create role
GET    /api/v1/access-control/roles/{role}             Show role
PUT    /api/v1/access-control/roles/{role}             Update role
DELETE /api/v1/access-control/roles/{role}             Soft-delete role
POST   /api/v1/access-control/roles/{role}/restore     Restore role
DELETE /api/v1/access-control/roles/{role}/force        Force-delete role

GET    /api/v1/access-control/permissions              List permissions
POST   /api/v1/access-control/permissions              Create permission
GET    /api/v1/access-control/permissions/{permission}  Show permission
PUT    /api/v1/access-control/permissions/{permission}  Update permission
DELETE /api/v1/access-control/permissions/{permission}  Soft-delete permission
POST   /api/v1/access-control/permissions/{permission}/restore  Restore
DELETE /api/v1/access-control/permissions/{permission}/force     Force-delete
```

All API routes require `auth:sanctum` middleware.

### API Controllers

Each controller follows the same authorization pattern using `$this->authorize()` with permission names:

| Controller | Model | Permission Prefix |
|------------|-------|-------------------|
| `UserController` | `User` | `access-control.users.*` |
| `RoleController` | `Role` | `access-control.roles.*` |
| `PermissionController` | `Permission` | `access-control.permissions.*` |

Controller methods: `index`, `store`, `show`, `update`, `destroy`, `restore`, `forceDelete`.

### Form Requests

| Request | Key Validations |
|---------|-----------------|
| `StoreUserRequest` | name (required), email (required, unique), password (required, confirmed), roles (array of existing role IDs) |
| `UpdateUserRequest` | name (required), email (unique ignoring current), password (optional, confirmed), roles (array) |
| `StoreRoleRequest` | name (required, unique per tenant+guard), guard_name, permissions (array) |
| `UpdateRoleRequest` | name (unique ignoring current per tenant+guard), guard_name, permissions (array) |
| `StorePermissionRequest` | name (required, unique per tenant+guard), guard_name |
| `UpdatePermissionRequest` | name (unique ignoring current per tenant+guard), guard_name |

### API Resources

| Resource | Fields |
|----------|--------|
| `UserResource` | id, name, email, email_verified_at, created_at, updated_at, deleted_at, roles (conditional), permissions (conditional) |
| `RoleResource` | id, name, guard_name, tenant_id, is_synced, created_at, updated_at, deleted_at, permissions (conditional), users (conditional) |
| `PermissionResource` | id, name, guard_name, tenant_id, is_synced, created_at, updated_at, deleted_at, roles (conditional) |

All timestamps are formatted as ISO 8601.

### Livewire Components

Each resource (Users, Roles, Permissions) has the following Livewire component set:

| Component | Responsibility |
|-----------|---------------|
| `Index` | Full-page data table with search, column filters, sorting, pagination, tab switching (active/trash), bulk selection |
| `CreateModal` | Create form with validation, role/permission assignment |
| `ShowModal` | Read-only detail view with metadata, related entities display |
| `EditModal` | Update form with record locking detection, avatar upload (users) |
| `DeleteModal` | Soft-delete confirmation with entity name display |
| `BulkDeleteModal` | Bulk soft-delete confirmation with count |
| `RestoreModal` | Soft-delete restoration confirmation |
| `ForceDeleteModal` | Permanent deletion confirmation with warning |
| `HistoryModal` | Audit trail display with event type, old/new value diffs, user attribution |
| `ImportExportModal` | Queued import (file upload with preview/validation) and export (polling for job completion, signed URL download) |
| `SyncModal` | (Roles and Permissions only) Push central templates to all tenants |

### Events

All events implement `ShouldBroadcastNow` and broadcast on tenant-scoped channels:

| Event | Channel Pattern |
|-------|-----------------|
| `UserRecordChanged` | `access-control.users.{tenantId}` or `access-control.users.central` |
| `RoleRecordChanged` | `access-control.roles.{tenantId}` or `access-control.roles.central` |
| `PermissionRecordChanged` | `access-control.permissions.{tenantId}` or `access-control.permissions.central` |

These events trigger real-time table refreshes in connected browser sessions.

### Media Handling

The Users resource supports avatar uploads:

- Collection: `users` (single file)
- Accepted types: PNG
- Storage: configured media disk
- Conversion: 150x150 `thumb` (non-queued)
- Path generator: `AccessControlPathGenerator` produces `access-control/{collection}/{model_id}/`
- File namer: `RandomFileNamer` generates 32-char hex filenames

---

## OrganisationSetup Module

### Purpose

Manages the tenant lifecycle including creation, configuration, domain assignment, user association, and data management operations.

### Route Configuration

**Web Routes** (`Modules/OrganisationSetup/routes/web.php`):

```
/organisation-setup/tenants    Tenants management page
```

Requires `auth`, `verified`, and `EnsureCentralAccess` middleware.

**API Routes**: Currently empty (reserved for future implementation).

### Livewire Components

| Component | Responsibility |
|-----------|---------------|
| `Index` | Full-page data table with search, filters, sorting, pagination |
| `CreateModal` | Tenant creation with optional custom ID (auto-generated slug) |
| `ShowModal` | Tenant details with metadata, domain list, user count |
| `EditModal` | Tenant update with record locking |
| `DeleteModal` | Soft-delete confirmation |
| `BulkDeleteModal` | Bulk soft-delete |
| `RestoreModal` | Restoration confirmation |
| `ForceDeleteModal` | Permanent deletion warning |
| `HistoryModal` | Full audit trail with change diffs |
| `ImportExportModal` | Queued import/export operations |
| `AssignDomainModal` | Add and remove subdomain associations |
| `TenantUserModal` | Add users by email with role assignment, list current users, remove users |
| `SyncMigrationsModal` | Run pending migrations, seeders (`TenantDatabaseSeeder`), and permission sync on all tenants |

### Events

| Event | Channel Pattern |
|-------|-----------------|
| `TenantRecordChanged` | `organisation-setup.tenants.{tenantId}` or `organisation-setup.tenants.central` |

### Export/Import

| Class | Description |
|-------|-------------|
| `TenantsExport` | Exports tenant ID, name, and created date |
| `TenantsImport` | Imports tenants with optional custom ID (validated as lowercase slug) |
| `ExportTenantsJob` | Queued export with signed URL caching |
| `ImportTenantsJob` | Queued import with result caching |

---

## Creating a New Module

### Generation

```bash
php artisan module:make NewModule --no-interaction
```

### Recommended Structure

Follow the AccessControl module as a reference implementation:

1. Create Livewire components for each resource (Index + CRUD modals).
2. Create Eloquent models extending `BaseModel` for automatic tenant scoping, UUIDs, auditing, and soft-deletes.
3. Create API controllers with permission-based authorization.
4. Create Form Request classes with validation rules and authorization checks.
5. Create API Resources for JSON response formatting.
6. Create broadcast events implementing `ShouldBroadcastNow` with tenant-scoped channels.
7. Create queued export/import jobs following the existing pattern.
8. Register routes in `routes/web.php` and `routes/api.php` with appropriate middleware.
9. Add permissions to the seeder and run `php artisan db:seed`.
10. Register Livewire components in `routes/tenant.php` for tenant context access.

### Module Route Provider Pattern

In subdomain tenancy mode, module routes should be constrained to the central domain. Follow the existing `RouteServiceProvider` pattern:

```php
public function boot(): void
{
    $centralDomain = config('tenancy.mode') === 'subdomain'
        ? (config('tenancy.central_domains')[0] ?? null)
        : null;

    $this->routes(function () use ($centralDomain) {
        $web = Route::middleware('web')
            ->prefix('new-module')
            ->name('new-module.');

        if ($centralDomain) {
            $web->domain($centralDomain);
        }

        $web->group(module_path('NewModule', '/routes/web.php'));
    });
}
```

### Sidebar Navigation

Add navigation entries to `resources/views/layouts/app/sidebar.blade.php` using `@can` or `@canany` directives for permission-based visibility.

### Tenant Route Registration

Duplicate module routes in `routes/tenant.php` for both subdomain and path tenancy modes, replacing `EnsureCentralAccess` with `EnsureTenantAccess` and using the `tenant.` route name prefix.
