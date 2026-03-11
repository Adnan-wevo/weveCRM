<?php

namespace App\Actions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Spatie\Permission\PermissionRegistrar;

/**
 * Copies central (tenant_id = null) roles and permissions to a specific tenant.
 *
 * Only applicable when TENANCY_MODE is "path" or "subdomain".
 * "super-admin" is a central-only role and is never copied to tenants.
 *
 * Behaviour:
 *  - All central permissions are cloned to the tenant (upsert by name+guard).
 *  - "admin" and "user" roles are cloned to the tenant.
 *  - Each tenant role is synced with the same permissions as its central template.
 *  - Spatie permission cache is cleared after the operation.
 */
class SyncRolesPermissionsToTenantAction
{
    /** Roles that are templates and should be copied to every tenant. */
    private const TENANT_ROLES = ['admin', 'user'];

    public function execute(Tenant $tenant): void
    {
        if (config('tenancy.mode', 'single') === 'single') {
            return;
        }

        $this->syncPermissions($tenant);
        $this->syncRoles($tenant);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Permission names prefixed with access-control.permissions.
     * that tenants are NOT allowed to have — only index and show are permitted.
     */
    private const TENANT_READONLY_PERMISSIONS_PREFIX = 'access-control.permissions.';

    private const TENANT_ALLOWED_PERMISSIONS_ACTIONS = ['index', 'show'];

    private function syncPermissions(Tenant $tenant): void
    {
        $central = Permission::central()
            ->withoutTrashed()
            ->where(function ($q): void {
                // Allow all permissions EXCEPT access-control.permissions.* write actions.
                $q->where('name', 'not like', self::TENANT_READONLY_PERMISSIONS_PREFIX.'%')
                    ->orWhereIn('name', array_map(
                        fn (string $action) => self::TENANT_READONLY_PERMISSIONS_PREFIX.$action,
                        self::TENANT_ALLOWED_PERMISSIONS_ACTIONS,
                    ));
            })
            ->get();

        foreach ($central as $perm) {
            Permission::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => $perm->name,
                    'guard_name' => $perm->guard_name,
                ],
                ['is_synced' => true],
            );
        }
    }

    private function syncRoles(Tenant $tenant): void
    {
        foreach (self::TENANT_ROLES as $roleName) {
            $centralRole = Role::central()
                ->withoutTrashed()
                ->where('name', $roleName)
                ->first();

            if ($centralRole === null) {
                continue;
            }

            $tenantRole = Role::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => $roleName,
                    'guard_name' => $centralRole->guard_name,
                ],
                ['is_synced' => true],
            );

            // Mirror the central role's permissions using tenant-specific copies.
            $permissionNames = $centralRole->permissions->pluck('name');

            $tenantPermissions = Permission::forTenant($tenant->id)
                ->withoutTrashed()
                ->whereIn('name', $permissionNames)
                ->get();

            $tenantRole->syncPermissions($tenantPermissions);
        }
    }
}
