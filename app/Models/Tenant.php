<?php

namespace App\Models;

use Abbasudo\Purity\Traits\Sortable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Multi-strategy tenant model.
 *
 * - TENANCY_MULTI_DB=false (default): Single-database tenancy. All tenants share
 *   the central database, scoped by tenant_id via the BelongsToTenant trait on
 *   each model. No per-tenant DB is created.
 *
 * - TENANCY_MULTI_DB=true: Multi-database tenancy. Each tenant gets its own
 *   database (prefixed with "tenant" + tenant id). Tenant-specific migrations
 *   live in database/migrations/tenant/. The DatabaseTenancyBootstrapper
 *   switches the connection automatically when tenancy is initialised.
 *
 * Custom attributes (name, plan, settings, etc.) are stored in the JSON 'data'
 * column automatically — no extra migration needed.
 *
 * @property string|null $name
 * @property \Carbon\Carbon|null $deleted_at
 */
class Tenant extends BaseTenant implements AuditableContract, HasMedia, TenantWithDatabase
{
    use Auditable, HasDatabase, HasDomains, InteractsWithMedia, SoftDeletes, Sortable;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user');
    }
}
