<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Abbasudo\Purity\Traits\Filterable;
use Abbasudo\Purity\Traits\Sortable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class User extends Authenticatable implements AuditableContract, HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Auditable, Filterable, HasApiTokens, HasFactory, HasRoles, HasUuids, Impersonate, InteractsWithMedia, Notifiable, SoftDeletes, Sortable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'keycloak_id',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user');
    }

    /**
     * Get the user's initials
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('users')
            ->singleFile()
            ->useDisk(config('media-library.disk_name', 'local'))
            ->acceptsMimeTypes(['image/png']);
    }

    /**
     * Register media conversions.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb') // @phpstan-ignore method.notFound
            ->width(150)
            ->height(150)
            ->nonQueued();
    }

    /**
     * Get the user's avatar URL (signed private URL, requires auth) or null.
     */
    public function avatarUrl(): ?string
    {
        $media = $this->getFirstMedia('users');

        if (! $media) {
            return null;
        }

        // Build the private-disk path: access-control/{collection}/{uuid}/{filename}
        $path = 'access-control/'.$media->collection_name.'/'.$media->model_id.'/'.$media->file_name;

        return URL::temporarySignedRoute('secure.media', now()->addMinutes(30), ['path' => $path]);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Only super-admins (central role) may impersonate other users.
     */
    public function canImpersonate(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Prevent impersonating oneself; all other users are eligible targets.
     */
    public function canBeImpersonated(): bool
    {
        return (string) $this->id !== (string) auth()->id();
    }

    /**
     * Override Spatie's hasPermissionTo so the permission string is resolved in the correct
     * tenant/central scope before the ID comparison in hasPermissionViaRole().
     *
     * Without this, Spatie calls Permission::findByName() globally and gets the central
     * record (tenant_id=NULL). That ID never matches the tenant-scoped permission IDs
     * returned by getPermissionsViaRoles(), causing all @can checks to fail on tenants.
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if (is_string($permission)) {
            $guardName = $guardName ?? $this->getDefaultGuardName();

            if (tenancy()->initialized) {
                $permission = Permission::forTenant(tenant('id'))
                    ->where('name', $permission)
                    ->where('guard_name', $guardName)
                    ->first();
            } else {
                $permission = Permission::central()
                    ->where('name', $permission)
                    ->where('guard_name', $guardName)
                    ->first();
            }

            if (! $permission) {
                return false;
            }
        }

        return $this->hasDirectPermission($permission) || $this->hasPermissionViaRole($permission);
    }

    /**
     * Override Spatie's default to scope permissions to the current tenant context.
     *
     * When tenancy is initialised, only roles belonging to that tenant are used.
     * In central context, only roles with null tenant_id are used.
     * This prevents roles (and their permissions) from bleeding across tenants.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission>
     */
    public function getPermissionsViaRoles(): \Illuminate\Support\Collection
    {
        return $this->loadMissing('roles', 'roles.permissions')
            ->roles
            ->filter(function ($role) {
                /** @var \App\Models\Role $role */
                if (tenancy()->initialized) {
                    return $role->tenant_id === tenant('id');
                }

                return $role->tenant_id === null;
            })
            ->flatMap(function ($role) {
                /** @var \App\Models\Role $role */
                return $role->permissions;
            })
            ->filter(function ($permission) {
                /** @var \App\Models\Permission $permission */
                if (tenancy()->initialized) {
                    return $permission->tenant_id === tenant('id');
                }

                return $permission->tenant_id === null;
            })
            ->unique('id')
            ->sortBy('id')
            ->values();
    }
}
