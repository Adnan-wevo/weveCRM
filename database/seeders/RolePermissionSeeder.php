<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed access-control permissions for users, roles, and permissions modules.
     *
     * Permission naming convention:  access-control.<module>.<action>
     *
     * Actions map to Livewire component functions:
     *   index   → Index::mount()          – guarded on page load & redirect
     *   create  → Index::openCreate()     – hides/guards "New …" button & modal trigger
     *   store   → CreateModal::create()   – guarded inside save function
     *   show    → Index::openShow()       – hides/guards "View" menu item & modal trigger
     *   edit    → Index::openEdit()       – hides/guards "Edit" menu item & modal trigger
     *   update  → EditModal::update()     – guarded inside save function
     *   destroy      → DeleteModal::delete()         – hides/guards "Delete" menu item, confirm & bulk-delete
     *   restore      → RestoreModal::restore()       – hides/guards "Restore" menu item in trash tab
     *   force-delete → ForceDeleteModal::forceDelete() – hides/guards "Delete Forever" menu item in trash tab
     *   import-export → ImportExportModal::open()    – hides/guards "Import / Export" button & modal
     *
     * Roles (kebab-case):
     *   super-admin → Gate::before bypass (no permissions needed; assigned in AppServiceProvider)
     *   admin       → users & roles: all except restore and force-delete
     *                 permissions: index and show only (write access is super-admin only)
     *   user        → dashboard.index only
     */
    public function run(): void
    {
        $modules = ['users', 'roles', 'permissions'];
        $actions = ['index', 'create', 'store', 'show', 'history', 'edit', 'update', 'destroy', 'restore', 'force-delete', 'import-export'];

        // admin gets everything on users/roles except restore and force-delete (trash is super-admin only)
        $adminActions = ['index', 'create', 'store', 'show', 'history', 'edit', 'update', 'destroy', 'import-export'];

        // permissions module: admin can only read — create/edit/delete is super-admin only
        $adminPermissionsModuleActions = ['index', 'show', 'history'];

        $allPermissions = [];
        $adminPermissions = [];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permission = Permission::firstOrCreate([
                    'tenant_id' => null,
                    'name' => "access-control.{$module}.{$action}",
                    'guard_name' => 'web',
                ]);

                $allPermissions[] = $permission;

                $allowedForAdmin = $module === 'permissions'
                    ? in_array($action, $adminPermissionsModuleActions)
                    : in_array($action, $adminActions);

                if ($allowedForAdmin) {
                    $adminPermissions[] = $permission;
                }
            }
        }

        // Sync permissions — central only (super-admin bypasses via Gate::before)
        $syncPermissions = [];
        foreach (['roles', 'permissions'] as $module) {
            $syncPermissions[] = Permission::firstOrCreate([
                'tenant_id' => null,
                'name' => "access-control.{$module}.sync",
                'guard_name' => 'web',
            ]);
        }

        // Impersonate permission — central only, super-admin exclusively (never synced to tenants).
        // No role is explicitly assigned this permission; super-admin bypasses all gates via Gate::before.
        Permission::firstOrCreate([
            'tenant_id' => null,
            'name' => 'access-control.users.impersonate',
            'guard_name' => 'web',
        ]);

        // dashboard.index permission for basic users
        $dashboardPermission = Permission::firstOrCreate([
            'tenant_id' => null,
            'name' => 'dashboard.index',
            'guard_name' => 'web',
        ]);

        // ── super-admin ──────────────────────────────────────────────────────────
        // Gate::before in AppServiceProvider bypasses all gates for this role.
        // No permissions need to be assigned here; the Gate bypass handles everything.
        // super-admin is CENTRAL ONLY (tenant_id = null).
        Role::firstOrCreate([
            'tenant_id' => null,
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        // ── admin ────────────────────────────────────────────────────────────────
        // Full access-control access including destroy, but excluding restore and force-delete.
        // permissions module: read-only. Sync permissions: central super-admin only.
        // admin is CENTRAL TEMPLATE (tenant_id = null); copied to each tenant on sync.
        /** @var Role $admin */
        $admin = Role::firstOrCreate([
            'tenant_id' => null,
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin->syncPermissions($adminPermissions);

        // ── user ─────────────────────────────────────────────────────────────────
        // Basic users can only access the dashboard.
        // user is CENTRAL TEMPLATE (tenant_id = null); copied to each tenant on sync.
        /** @var Role $user */
        $user = Role::firstOrCreate([
            'tenant_id' => null,
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        $user->syncPermissions([$dashboardPermission]);

        $this->command->info(sprintf(
            'Created/verified %d access-control permissions + 2 sync + impersonate + dashboard.index (all central).',
            count($allPermissions),
        ));

        $this->command->info('Roles seeded: super-admin central-only, admin + user as central templates (tenant_id = null).');
    }
}
