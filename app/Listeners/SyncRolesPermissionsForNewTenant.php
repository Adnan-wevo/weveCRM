<?php

namespace App\Listeners;

use App\Actions\SyncRolesPermissionsToTenantAction;
use Stancl\Tenancy\Events\TenantCreated;

/**
 * When a new tenant is created (path / subdomain mode), seed tenant-scoped
 * copies of the central "admin" and "user" roles and all permissions from
 * the central templates so the tenant has a working permission set immediately.
 */
class SyncRolesPermissionsForNewTenant
{
    public function __construct(public readonly SyncRolesPermissionsToTenantAction $action) {}

    public function handle(TenantCreated $event): void
    {
        /** @var \App\Models\Tenant $tenant */
        $tenant = $event->tenant;
        $this->action->execute($tenant);
    }
}
