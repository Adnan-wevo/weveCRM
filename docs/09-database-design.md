# Database Design

## Overview

The application uses MariaDB 11.4 as the primary relational database. All tenants share a single database (single-database tenancy). Tenant isolation is enforced through `tenant_id` columns on scoped tables. Primary keys use UUID v4 strings.

## Entity Relationship Diagram

```
+-------------------+       +-------------------+       +-------------------+
|      users        |       |    tenant_user    |       |     tenants       |
+-------------------+       +-------------------+       +-------------------+
| id (UUID PK)      |<----->| user_id (FK)      |<----->| id (string PK)    |
| name              |       | tenant_id (FK)    |       | data (JSON)       |
| email (unique)    |       +-------------------+       | created_at        |
| email_verified_at |                                   | updated_at        |
| password (null)   |                                   | deleted_at        |
| keycloak_id (null)|                                   +-------------------+
| two_factor_*      |                                           |
| remember_token    |                                   +-------v-----------+
| created_at        |                                   |     domains       |
| updated_at        |                                   +-------------------+
| deleted_at        |                                   | id (PK)           |
+-------------------+                                   | domain (unique)   |
        |                                               | tenant_id (FK)    |
        |       +-------------------+                   +-------------------+
        +------>| model_has_roles   |
        |       +-------------------+
        |       | role_id (FK)      |
        |       | model_type        |
        |       | model_id (UUID)   |       +-------------------+
        |       +-------------------+       |      roles        |
        |               |                  +-------------------+
        |               +----------------->| id (UUID PK)      |
        |                                  | name              |
        |       +-------------------+      | guard_name        |
        +------>| model_has_perms   |      | tenant_id (FK)    |
                +-------------------+      | is_synced         |
                | permission_id(FK) |      | created_at        |
                | model_type        |      | updated_at        |
                | model_id (UUID)   |      | deleted_at        |
                +-------------------+      +-------------------+
                        |                          |
                        |   +----------------------+
                        |   |
                        v   v
                +-------------------+
                |   permissions     |
                +-------------------+
                | id (UUID PK)      |
                | name              |
                | guard_name        |
                | tenant_id (FK)    |
                | is_synced         |
                | created_at        |
                | updated_at        |
                | deleted_at        |
                +-------------------+
                        ^
                        |
                +-------------------+
                | role_has_perms    |
                +-------------------+
                | permission_id(FK) |
                | role_id (FK)      |
                +-------------------+
```

## Tables

### Core Tables

#### `users`

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | UUID | No | Primary key |
| `name` | varchar(255) | No | Display name |
| `email` | varchar(255) | No | Unique email address |
| `email_verified_at` | timestamp | Yes | Email verification timestamp |
| `password` | varchar(255) | Yes | Hashed password (null for SSO-only users) |
| `keycloak_id` | varchar(255) | Yes | Keycloak subject ID (unique) |
| `two_factor_secret` | text | Yes | Encrypted TOTP secret |
| `two_factor_recovery_codes` | text | Yes | Encrypted JSON recovery codes |
| `two_factor_confirmed_at` | timestamp | Yes | 2FA confirmation timestamp |
| `remember_token` | varchar(100) | Yes | Remember me token |
| `created_at` | timestamp | Yes | Creation timestamp |
| `updated_at` | timestamp | Yes | Last update timestamp |
| `deleted_at` | timestamp | Yes | Soft-delete timestamp |

#### `tenants`

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | varchar(255) | No | Primary key (UUID or custom slug) |
| `data` | JSON | Yes | Arbitrary tenant attributes (name, plan, settings) |
| `created_at` | timestamp | Yes | Creation timestamp |
| `updated_at` | timestamp | Yes | Last update timestamp |
| `deleted_at` | timestamp | Yes | Soft-delete timestamp |

#### `domains`

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint | No | Auto-incrementing primary key |
| `domain` | varchar(255) | No | Unique domain/subdomain string |
| `tenant_id` | varchar(255) | No | Foreign key to tenants.id |

#### `tenant_user`

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `tenant_id` | varchar(255) | No | Foreign key to tenants.id |
| `user_id` | UUID | No | Foreign key to users.id |

Composite primary key: `(tenant_id, user_id)`.

### Authorization Tables

#### `roles`

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | UUID | No | Primary key |
| `name` | varchar(255) | No | Role name |
| `guard_name` | varchar(255) | No | Auth guard name |
| `tenant_id` | varchar(255) | Yes | Tenant scope (null = central) |
| `is_synced` | boolean | No | Whether created by sync process |
| `created_at` | timestamp | Yes | Creation timestamp |
| `updated_at` | timestamp | Yes | Last update timestamp |
| `deleted_at` | timestamp | Yes | Soft-delete timestamp |

Unique constraint: `(tenant_id, name, guard_name)`.

#### `permissions`

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | UUID | No | Primary key |
| `name` | varchar(255) | No | Permission name |
| `guard_name` | varchar(255) | No | Auth guard name |
| `tenant_id` | varchar(255) | Yes | Tenant scope (null = central) |
| `is_synced` | boolean | No | Whether created by sync process |
| `created_at` | timestamp | Yes | Creation timestamp |
| `updated_at` | timestamp | Yes | Last update timestamp |
| `deleted_at` | timestamp | Yes | Soft-delete timestamp |

Unique constraint: `(tenant_id, name, guard_name)`.

#### `model_has_roles`

| Column | Type | Description |
|--------|------|-------------|
| `role_id` | UUID | Foreign key to roles.id |
| `model_type` | varchar(255) | Polymorphic model class |
| `model_id` | UUID | Polymorphic model ID |

#### `model_has_permissions`

| Column | Type | Description |
|--------|------|-------------|
| `permission_id` | UUID | Foreign key to permissions.id |
| `model_type` | varchar(255) | Polymorphic model class |
| `model_id` | UUID | Polymorphic model ID |

#### `role_has_permissions`

| Column | Type | Description |
|--------|------|-------------|
| `permission_id` | UUID | Foreign key to permissions.id |
| `role_id` | UUID | Foreign key to roles.id |

### Infrastructure Tables

#### `audits`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Auto-incrementing primary key |
| `user_type` | varchar(255) | Polymorphic user model class |
| `user_id` | UUID | Polymorphic user ID |
| `event` | varchar(255) | Event type (created, updated, deleted, restored) |
| `auditable_type` | varchar(255) | Polymorphic audited model class |
| `auditable_id` | UUID | Polymorphic audited model ID |
| `old_values` | text | JSON of previous values |
| `new_values` | text | JSON of new values |
| `url` | text | Request URL |
| `ip_address` | varchar(45) | Client IP |
| `user_agent` | varchar(1023) | Client user agent |
| `tags` | varchar(255) | Optional tags |
| `created_at` | timestamp | Audit timestamp |
| `updated_at` | timestamp | Update timestamp |

#### `media`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Auto-incrementing primary key |
| `model_type` | varchar(255) | Polymorphic model class (UUID morphs) |
| `model_id` | UUID | Polymorphic model ID |
| `uuid` | UUID | Unique media identifier |
| `collection_name` | varchar(255) | Media collection name |
| `name` | varchar(255) | Original filename |
| `file_name` | varchar(255) | Stored filename |
| `mime_type` | varchar(255) | MIME type |
| `disk` | varchar(255) | Storage disk name |
| `conversions_disk` | varchar(255) | Conversions storage disk |
| `size` | bigint | File size in bytes |
| `manipulations` | JSON | Image manipulation config |
| `custom_properties` | JSON | Custom metadata |
| `generated_conversions` | JSON | Generated conversion status |
| `responsive_images` | JSON | Responsive image metadata |
| `order_column` | int | Sort order |
| `created_at` | timestamp | Upload timestamp |
| `updated_at` | timestamp | Update timestamp |

#### `personal_access_tokens`

Sanctum token storage with UUID polymorphic `tokenable` relationship.

#### `magic_logins`

Passwordless login link storage with UUID polymorphic `authenticatable` relationship, login count, guard, redirect URL, expiration, and metadata.

#### `sessions`

Session storage (when using database driver).

#### `cache` / `cache_locks`

Cache storage (when using database driver).

#### `jobs` / `job_batches` / `failed_jobs`

Queue infrastructure tables.

#### `telescope_entries` / `telescope_entries_tags` / `telescope_monitoring`

Telescope debug storage.

#### `password_reset_tokens`

Password reset token storage.

## UUID Strategy

All domain models use UUID v4 primary keys via Laravel's `HasUuids` trait. This provides:

- Globally unique identifiers without sequential enumeration.
- Safe for distributed systems and cross-tenant references.
- No auto-incrementing integer exposure in URLs or APIs.

Infrastructure tables (`audits`, `media`, `jobs`) use auto-incrementing bigint primary keys for performance.

## Soft Deletes

All domain models implement `SoftDeletes`:

- `users` -- Soft-deletable.
- `tenants` -- Soft-deletable.
- `roles` -- Soft-deletable.
- `permissions` -- Soft-deletable.

Soft-deleted records are accessible via the "Trash" tab in management interfaces and can be restored or permanently deleted.

## Factories

### UserFactory

**Default state**: Verified user with hashed password (`password`).

**Available states**:

| State | Effect |
|-------|--------|
| `unverified()` | Sets `email_verified_at` to null |
| `withTwoFactor()` | Generates encrypted 2FA secret, recovery codes, and confirms 2FA |

## Seeders

### DatabaseSeeder

Orchestrates all **central** seeders in order:

1. `RolePermissionSeeder`
2. `TenantSeeder`

This seeder should **only** be used for the central database. For tenant databases, use `TenantDatabaseSeeder`.

### RolePermissionSeeder

Seeds all access-control permissions and creates three central roles (`super-admin`, `admin`, `user`) with appropriate permission assignments.

### TenantSeeder

Seeds organisation-setup permissions (including `sync-migrations`) and extends the `admin` role with tenant management permissions.

### TenantDatabaseSeeder

Root seeder for **tenant databases only**. Called by `tenants:seed` and the "Sync Migrations & Seed" UI button. This seeder never touches central tables (e.g. `permissions`, `roles`). Add tenant-specific seeders to its `$this->call()` array.

## Migration History

| Migration | Tables Affected |
|-----------|----------------|
| `0001_01_01_000000` | `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001` | `cache`, `cache_locks` |
| `0001_01_01_000002` | `jobs`, `job_batches`, `failed_jobs` |
| `2019_09_15_000010` | `tenants` |
| `2019_09_15_000020` | `domains` |
| `2025_08_14_170933` | `users` (add 2FA columns) |
| `2026_02_20_021700` | `permissions`, `roles`, `model_has_*`, `role_has_permissions` |
| `2026_02_20_022135` | `audits` |
| `2026_02_20_023007` | `telescope_entries`, `telescope_entries_tags`, `telescope_monitoring` |
| `2026_02_20_074359` | `media` |
| `2026_02_20_081056` | `users` (add soft deletes) |
| `2026_02_20_081057` | `permissions` (add soft deletes) |
| `2026_02_20_081057` | `roles` (add soft deletes) |
| `2026_02_20_090827` | `users` (add keycloak_id, nullable password) |
| `2026_02_20_122731` | `magic_logins` |
| `2026_02_20_130728` | `magic_logins` (fix morphs to uuid morphs) |
| `2026_02_20_132914` | `personal_access_tokens` |
| `2026_02_20_171354` | `tenants` (add soft deletes) |
| `2026_02_21_010330` | `tenant_user` |
| `2026_02_21_024158` | `tenant_user` (drop timestamps) |
| `2026_02_21_030505` | `roles`, `permissions` (add tenant_id, update unique constraint) |
| `2026_02_21_031800` | `roles`, `permissions` (add is_synced) |
