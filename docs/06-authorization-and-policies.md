# Authorization and Policies

## Overview

Authorization is implemented using `spatie/laravel-permission` (v7) with tenant-scoped extensions. The system uses a role-based access control (RBAC) model where permissions are assigned to roles, and roles are assigned to users. A `super-admin` role bypasses all permission checks via a `Gate::before` callback.

## Gate Configuration

The `AppServiceProvider` registers a global gate bypass:

```php
Gate::before(function ($user, $ability) {
    return $user->hasRole('super-admin') ? true : null;
});
```

This grants super-admin users implicit access to all gates and policies. Returning `null` for non-super-admins allows normal permission resolution to proceed.

## Permission Naming Convention

Permissions follow a hierarchical dot-notation pattern:

```
{module}.{resource}.{action}
```

### Access Control Module Permissions

| Permission | Purpose |
|------------|---------|
| `access-control.users.index` | View users list |
| `access-control.users.create` | Access create user form |
| `access-control.users.store` | Save new user |
| `access-control.users.show` | View user details |
| `access-control.users.history` | View user audit history |
| `access-control.users.edit` | Access edit user form |
| `access-control.users.update` | Save user changes |
| `access-control.users.destroy` | Soft-delete user |
| `access-control.users.restore` | Restore soft-deleted user |
| `access-control.users.force-delete` | Permanently delete user |
| `access-control.users.import-export` | Import/export user data |
| `access-control.roles.index` | View roles list |
| `access-control.roles.create` | Access create role form |
| `access-control.roles.store` | Save new role |
| `access-control.roles.show` | View role details |
| `access-control.roles.history` | View role audit history |
| `access-control.roles.edit` | Access edit role form |
| `access-control.roles.update` | Save role changes |
| `access-control.roles.destroy` | Soft-delete role |
| `access-control.roles.restore` | Restore soft-deleted role |
| `access-control.roles.force-delete` | Permanently delete role |
| `access-control.roles.import-export` | Import/export role data |
| `access-control.roles.sync` | Sync roles to tenants |
| `access-control.permissions.index` | View permissions list |
| `access-control.permissions.create` | Access create permission form |
| `access-control.permissions.store` | Save new permission |
| `access-control.permissions.show` | View permission details |
| `access-control.permissions.history` | View permission audit history |
| `access-control.permissions.edit` | Access edit permission form |
| `access-control.permissions.update` | Save permission changes |
| `access-control.permissions.destroy` | Soft-delete permission |
| `access-control.permissions.restore` | Restore soft-deleted permission |
| `access-control.permissions.force-delete` | Permanently delete permission |
| `access-control.permissions.import-export` | Import/export permission data |
| `access-control.permissions.sync` | Sync permissions to tenants |
| `dashboard.index` | Access dashboard |

### Organisation Setup Module Permissions

| Permission | Purpose |
|------------|---------|
| `organisation-setup.tenants.index` | View tenants list |
| `organisation-setup.tenants.create` | Access create tenant form |
| `organisation-setup.tenants.store` | Save new tenant |
| `organisation-setup.tenants.show` | View tenant details |
| `organisation-setup.tenants.history` | View tenant audit history |
| `organisation-setup.tenants.edit` | Access edit tenant form |
| `organisation-setup.tenants.update` | Save tenant changes |
| `organisation-setup.tenants.destroy` | Soft-delete tenant |
| `organisation-setup.tenants.restore` | Restore soft-deleted tenant |
| `organisation-setup.tenants.force-delete` | Permanently delete tenant |
| `organisation-setup.tenants.import-export` | Import/export tenant data |
| `organisation-setup.tenants.add-user` | Add users to tenant |
| `organisation-setup.tenants.remove-user` | Remove users from tenant |
| `organisation-setup.tenants.assign-domain` | Manage tenant domains |
| `organisation-setup.tenants.sync-migrations` | Run sync migrations & seed on all tenants |

## Default Roles

Three roles are seeded at the central level (`tenant_id = null`):

### super-admin

- Not assigned any explicit permissions.
- Bypasses all permission checks via `Gate::before`.
- Cannot access tenant routes (redirected by `EnsureTenantAccess`).
- Never synced to tenants.

### admin

Assigned all permissions for users and roles modules except `restore` and `force-delete`. For permissions module, granted `index`, `show`, and `history` only. Granted all tenant management permissions except `restore` and `force-delete` (including `sync-migrations`).

### user

Assigned `dashboard.index` only. Serves as the baseline role for authenticated users.

## Tenant-Scoped RBAC

### Data Model

Roles and permissions carry a `tenant_id` column:

- `tenant_id = null` -- Central role/permission (template).
- `tenant_id = {uuid}` -- Tenant-specific role/permission.

The unique constraint on roles and permissions is `(tenant_id, name, guard_name)`, allowing the same permission name to exist independently across tenants.

### Scopes

Both `Role` and `Permission` models provide query scopes:

| Scope | SQL Condition | Purpose |
|-------|---------------|---------|
| `central()` | `WHERE tenant_id IS NULL` | Query central templates |
| `forTenant($id)` | `WHERE tenant_id = $id` | Query tenant-specific records |

### Tenant Permission Resolution

The `User` model overrides two Spatie methods to enforce tenant-scoped permission resolution:

**`hasPermissionTo($permission)`**: Before checking the permission, resolves the permission object to the correct tenant scope. If tenancy is initialized, looks up the tenant-scoped version of the permission. If not found in tenant scope, falls back to central.

**`getPermissionsViaRoles()`**: Filters the user's roles to only those matching the current tenant context, then collects permissions from those roles.

This ensures that a user with the `admin` role in Tenant A sees only Tenant A's permissions, even if they also have roles in Tenant B.

### Tenant Synchronization

When a new tenant is created, the `SyncRolesPermissionsForNewTenant` listener triggers `SyncRolesPermissionsToTenantAction`:

1. Copies all central permissions to the new tenant (with `is_synced = true` and matching `tenant_id`).
2. Excludes write permissions on the permissions module (tenants get `index` and `show` only for permissions).
3. Copies `admin` and `user` roles (not `super-admin`) to the tenant.
4. Syncs each tenant role with the same permissions as its central counterpart.
5. Clears the Spatie permission cache.

The `is_synced` flag indicates that a role or permission was created by the sync process rather than manually.

### Manual Sync

Central administrators can manually trigger synchronization for existing tenants using the `SyncModal` Livewire component, available on the roles and permissions management pages.

### Sync Migrations & Seed

The `SyncMigrationsModal` Livewire component (on the Tenants page) allows administrators to run pending migrations, tenant-specific seeders (`TenantDatabaseSeeder`), and permission sync across all tenants in a single operation. Requires the `organisation-setup.tenants.sync-migrations` permission.

## Middleware Guards

### Route-Level Protection

| Middleware | Purpose |
|------------|---------|
| `auth` | Requires authentication; tenant-aware redirect |
| `verified` | Requires email verification |
| `EnsureCentralAccess` | Blocks non-super-admin tenant users from central routes |
| `EnsureTenantAccess` | Blocks super-admins and foreign tenant users from tenant routes |
| `role:{role}` | Requires specific role |
| `permission:{permission}` | Requires specific permission |
| `role_or_permission:{value}` | Requires either role or permission |

### View-Level Protection

Blade templates use `@can`, `@canany`, and `@cannot` directives to conditionally render UI elements:

```html
@canany(['access-control.users.index', 'access-control.roles.index', 'access-control.permissions.index'])
    <!-- Navigation group visible -->
@endcanany

@can('access-control.users.create')
    <!-- Create button visible -->
@endcan
```

### Controller-Level Protection

API controllers use `$this->authorize()` calls at the start of each method:

```php
public function store(StoreUserRequest $request): UserResource
{
    $this->authorize('access-control.users.store');
    // ...
}
```

## Adding New Permissions

1. Define the permission names following the `{module}.{resource}.{action}` convention.
2. Add them to the appropriate seeder (`RolePermissionSeeder` or `TenantSeeder`).
3. Assign them to roles in the seeder.
4. Run `php artisan db:seed --class=RolePermissionSeeder`.
5. Use `$this->authorize()` in controllers and `@can` in views.
6. If tenants should receive the new permissions, trigger a sync operation.
