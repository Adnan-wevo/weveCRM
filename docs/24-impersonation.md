# Impersonation

## Overview

Super-admins can temporarily authenticate as any other user without knowing their credentials. Every impersonation session is logged with a mandatory reason, timestamps, IP address, and user-agent for a complete audit trail.

The feature is built on [`lab404/laravel-impersonate`](https://github.com/404labfr/laravel-impersonate) and follows the application's standard Livewire modal pattern.

---

## Package

| Package | Version |
|---------|---------|
| `lab404/laravel-impersonate` | `^1.7` |

Auto-discovered via Laravel's package discovery — no manual registration in `bootstrap/app.php` or `config/app.php` is required.

---

## Authorisation

| Concern | Rule |
|---------|------|
| Who can impersonate | `super-admin` role only (`canImpersonate()` returns `$this->hasRole('super-admin')`) |
| Who can be impersonated | Any user except self (`canBeImpersonated()` blocks self-impersonation) |
| Permission | `access-control.users.impersonate` (central only, never synced to tenants) |
| Gate check | `Gate::before` bypass applies — super-admin always passes, other roles are blocked by `$this->authorize()` in the modal |

The `access-control.users.impersonate` permission exists only in the central context (`tenant_id = null`). It is seeded in `database/seeders/RolePermissionSeeder.php` as a standalone `Permission::firstOrCreate()` call, outside the modules/actions loops so it is never copied to tenants during role sync.

---

## User Model Traits

`app/Models/User.php` uses the `Lab404\Impersonate\Models\Impersonate` trait and declares two authorisation callbacks:

```php
use Lab404\Impersonate\Models\Impersonate;

// Only super-admins may impersonate
public function canImpersonate(): bool
{
    return $this->hasRole('super-admin');
}

// Prevent impersonating oneself
public function canBeImpersonated(): bool
{
    return (string) $this->id !== (string) auth()->id();
}
```

---

## Routes

Registered in `routes/web.php` under `auth` + `verified` middleware:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::impersonate();
});
```

This macro registers two named routes:

| Route name | Method | Description |
|------------|--------|-------------|
| `impersonate` | `GET` | Take impersonation (`/impersonate/{id}`) |
| `impersonate.leave` | `GET` | Leave current impersonation session |

---

## Audit Log

### Migration

`database/migrations/2026_02_21_173710_create_impersonation_logs_table.php`

| Column | Type | Notes |
|--------|------|-------|
| `id` | `uuid` | Primary key |
| `impersonator_id` | `uuid` | FK → `users` (cascade delete) |
| `impersonated_id` | `uuid` | FK → `users` (cascade delete) |
| `reason` | `text` | Mandatory reason entered in the modal |
| `ip_address` | `string(45)` | Nullable — client IP at the time of impersonation |
| `user_agent` | `text` | Nullable — browser/client identifier |
| `started_at` | `timestamp` | Set at the moment impersonation begins |
| `ended_at` | `timestamp` | Nullable — set when the session ends (`LeaveImpersonation` event) |
| `deleted_at` | `timestamp` | Soft delete (inherited from `BaseModel`) |
| `created_at` / `updated_at` | `timestamp` | Standard Eloquent timestamps |

### Model

`app/Models/ImpersonationLog.php` extends `BaseModel`, inheriting `HasUuids`, `SoftDeletes`, `Auditable`, `Filterable`, and `Sortable`.

Because `impersonation_logs` is a central-only table with no `tenant_id` column, the `BelongsToTenant` trait that `BaseModel` includes is disabled by overriding its boot method:

```php
// Disable BelongsToTenant global scope — central-only table
public static function bootBelongsToTenant(): void {}
```

### Relationships

```php
$log->impersonator; // User who performed the impersonation
$log->impersonated; // User who was impersonated
```

### Closing the Log on Leave

`app/Listeners/RecordImpersonationEnd.php` listens to the `Lab404\Impersonate\Events\LeaveImpersonation` event and sets `ended_at` on the most recent open log entry for that impersonator/impersonated pair.

Registered in `app/Providers/AppServiceProvider.php`:

```php
Event::listen(LeaveImpersonation::class, RecordImpersonationEnd::class);
```

---

## UI

### Triggering Impersonation

In the Access Control → Users table, each row's ellipsis dropdown includes an **Impersonate** menu item protected by `@can('access-control.users.impersonate')`:

```blade
@can('access-control.users.impersonate')
<flux:menu.separator />
<flux:menu.item icon="identification" wire:click="openImpersonate('{{ $user->id }}')">
    {{ __('Impersonate') }}
</flux:menu.item>
@endcan
```

`Index::openImpersonate()` authorises the action then dispatches the `open-impersonate-user` Livewire event.

### ImpersonateModal Component

| File | Path |
|------|------|
| PHP class | `Modules/AccessControl/app/Livewire/Users/ImpersonateModal.php` |
| Blade view | `Modules/AccessControl/resources/views/livewire/users/impersonate-modal.blade.php` |
| Livewire tag | `@livewire('accesscontrol::users.impersonate-modal')` |

The modal:
1. Displays the target user's name and email.
2. Requires a **reason** (3–500 characters, validated server-side with `#[Validate]`).
3. On confirmation — writes the `ImpersonationLog`, stores the leave redirect in session, calls `auth()->user()->impersonate($target)`, then redirects to the dashboard.

The session key `laravel-impersonate:leave_redirect_to` is set to `'access-control.users'` so that clicking "Leave Impersonation" returns the super-admin to the users page.

### Leave Impersonation Banner

The sidebar (`resources/views/layouts/app/sidebar.blade.php`) uses the `@impersonating` / `@endImpersonating` Blade directives to display a banner above the spacer when an active impersonation session is in progress:

```blade
@impersonating
<div class="mx-3 mb-2 rounded-lg bg-amber-500/20 px-3 py-2 ring-1 ring-amber-400/40">
    <p>Impersonating</p>
    <p>{{ auth()->user()->name }}</p>
    <a href="{{ route('impersonate.leave') }}">Leave Impersonation</a>
</div>
@endImpersonating
```

---

## Data Flow Summary

```
Super-admin opens ImpersonateModal
  → enters reason → submits
    → ImpersonationLog::create() (started_at set, ended_at null)
    → session key stored for leave redirect
    → auth()->user()->impersonate($target)
    → redirect to dashboard (as impersonated user)

Super-admin clicks "Leave Impersonation"
  → route('impersonate.leave') called
    → Lab404 fires LeaveImpersonation event
      → RecordImpersonationEnd sets ended_at on open log
    → redirect to access-control.users
```

---

## Blade Directives

The package registers three Blade directives:

| Directive | Usage |
|-----------|-------|
| `@canImpersonate` / `@endCanImpersonate` | Renders content only when the authenticated user can impersonate |
| `@canBeImpersonated($user)` / `@endCanBeImpersonated` | Renders content only when `$user` can be impersonated |
| `@impersonating` / `@endImpersonating` | Renders content only when an impersonation session is active |
