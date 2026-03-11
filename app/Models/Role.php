<?php

namespace App\Models;

use Abbasudo\Purity\Traits\Filterable;
use Abbasudo\Purity\Traits\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property bool $is_synced
 * @property string|null $tenant_id
 * @property \Carbon\Carbon|null $deleted_at
 */
class Role extends SpatieRole implements AuditableContract
{
    use Auditable, Filterable, HasUuids, SoftDeletes, Sortable;

    /**
     * The table associated with this model.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * Additional fillable attributes beyond Spatie defaults.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'guard_name', 'tenant_id', 'is_synced'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_synced' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Roles that belong to the central app (no tenant). */
    public function scopeCentral(Builder $query): Builder
    {
        return $query->whereNull('tenant_id');
    }

    /** Roles that belong to a specific tenant. */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}
