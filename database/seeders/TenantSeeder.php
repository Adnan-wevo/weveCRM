<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Seed organisation-setup permissions for the tenants module.
     *
     * Permission naming convention:  organisation-setup.tenants.<action>
     *
     * Actions map to Livewire component functions:
     *   index   → Index::mount()                    – guarded on page load & redirect
     *   create  → Index::openCreate()               – hides/guards "New Tenant" button & modal trigger
     *   store   → CreateModal::create()             – guarded inside save function
     *   show    → Index::openShow()                 – hides/guards "View" menu item & modal trigger
     *   edit    → Index::openEdit()                 – hides/guards "Edit" menu item & modal trigger
     *   update  → EditModal::update()               – guarded inside save function
     *   destroy      → DeleteModal::delete()           – hides/guards "Delete" menu item, confirm & bulk-delete
     *   restore      → RestoreModal::restore()         – hides/guards "Restore" menu item in trash tab
     *   force-delete → ForceDeleteModal::forceDelete() – hides/guards "Delete Forever" in trash tab
     *   import-export    → ImportExportModal            – hides/guards "Import / Export" button & modal
     *   sync-migrations  → SyncMigrationsModal          – hides/guards "Sync Migrations & Seed" button & modal
     *
     * Roles:
     *   super-admin → Gate::before bypass (no permissions needed)
     *   admin       → all organisation-setup.tenants permissions except restore and force-delete
     */
    public function run(): void
    {
        $actions = ['index', 'create', 'store', 'show', 'history', 'edit', 'update', 'destroy', 'restore', 'force-delete', 'import-export', 'add-user', 'remove-user', 'assign-domain', 'sync-migrations'];

        // admin gets everything except restore and force-delete (trash management is super-admin only)
        $adminActions = ['index', 'create', 'store', 'show', 'history', 'edit', 'update', 'destroy', 'import-export', 'add-user', 'remove-user', 'assign-domain', 'sync-migrations'];

        $allPermissions = [];
        $adminPermissions = [];

        foreach ($actions as $action) {
            $permission = Permission::firstOrCreate([
                'tenant_id' => null,
                'name' => "organisation-setup.tenants.{$action}",
                'guard_name' => 'web',
            ]);

            $allPermissions[] = $permission;

            if (in_array($action, $adminActions)) {
                $adminPermissions[] = $permission;
            }
        }

        // ── admin ─────────────────────────────────────────────────────────────────
        // Sync organisation-setup.tenants permissions into the admin role.
        /** @var Role $admin */
        $admin = Role::firstOrCreate([
            'tenant_id' => null,
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        // Merge new permissions with existing admin permissions (avoid removing others)
        $existingPermissions = $admin->permissions->pluck('id')->toArray();
        $newPermissionIds = collect($adminPermissions)->pluck('id')->toArray();
        $admin->syncPermissions(array_unique(array_merge($existingPermissions, $newPermissionIds)));

        $this->command->info(sprintf(
            'Created/verified %d organisation-setup.tenants permissions.',
            count($allPermissions),
        ));

        $this->command->info('Admin role updated with organisation-setup.tenants permissions (excluding restore and force-delete).');
    }
}
