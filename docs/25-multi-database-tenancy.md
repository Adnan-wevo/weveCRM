# Multi-Database Tenancy

## Overview

This application supports two database isolation strategies for multi-tenancy, controlled by a single environment variable:

| Strategy | `TENANCY_MULTI_DB` | How It Works |
|----------|-------------------|--------------|
| **Single-Database** (default) | `false` | All tenants share one MariaDB database. Isolation is enforced at the application level via `tenant_id` columns and the `BelongsToTenant` trait on `BaseModel`. |
| **Multi-Database** | `true` | Each tenant gets its own database (e.g. `tenantacme`). The `DatabaseTenancyBootstrapper` switches the active DB connection when tenancy is initialised. |

Both strategies work with all three tenancy modes (`single`, `subdomain`, `path`).

---

## Environment Configuration

Toggle multi-database tenancy in `.env`:

```env
# false = single-database (default), true = multi-database
TENANCY_MULTI_DB=false
```

No other configuration changes are needed. The rest is handled automatically by `config/tenancy.php` and `TenancyServiceProvider`.

---

## How It Works

### Config: `config/tenancy.php`

The `DatabaseTenancyBootstrapper` is conditionally loaded based on the env toggle:

```php
'bootstrappers' => array_values(array_filter([
    env('TENANCY_MULTI_DB', false)
        ? Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class
        : null,
    Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
    Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
    Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
])),
```

A `database.multi_db` config key is also set for runtime checks:

```php
'database' => [
    'multi_db' => env('TENANCY_MULTI_DB', false),
    // ...
],
```

### Tenant Model: `app/Models/Tenant.php`

The `Tenant` model implements `TenantWithDatabase` and uses the `HasDatabase` trait, enabling stancl/tenancy to manage per-tenant database connections:

```php
class Tenant extends BaseTenant implements AuditableContract, HasMedia, TenantWithDatabase
{
    use Auditable, HasDatabase, HasDomains, InteractsWithMedia, SoftDeletes, Sortable;
}
```

These interfaces are safe in single-db mode — stancl only invokes `HasDatabase` functionality when the `DatabaseTenancyBootstrapper` is active.

### Service Provider: `app/Providers/TenancyServiceProvider.php`

The provider adapts its behaviour based on the database strategy:

#### Multi-DB Mode (`TENANCY_MULTI_DB=true`)

On `TenantCreated`, a job pipeline runs:

1. `Jobs\CreateDatabase` — Creates the tenant database (e.g. `tenantacme`).
2. `Jobs\MigrateDatabase` — Runs all migrations from `database/migrations/tenant/`.

On `TenantDeleted`:

1. `Jobs\DeleteDatabase` — Drops the tenant database.

#### Single-DB Mode (`TENANCY_MULTI_DB=false`)

- No database creation or deletion jobs run.
- `$this->loadMigrationsFrom(database_path('migrations/tenant'))` is called, which registers tenant migrations to run against the central database during `php artisan migrate`.

In both modes, `SyncRolesPermissionsForNewTenant` listener always fires on `TenantCreated`.

---

## Tenant Migrations

Tenant-specific migrations live in `database/migrations/tenant/`. This path is used by:

- **Multi-DB mode**: The `MigrateDatabase` job via `config('tenancy.migration_parameters.--path')`.
- **Single-DB mode**: `TenancyServiceProvider::boot()` via `$this->loadMigrationsFrom()`.

### Tenant Seeding

Tenant databases use `TenantDatabaseSeeder` — **not** the central `DatabaseSeeder`. This is configured in `config/tenancy.php`:

```php
'seeder_parameters' => [
    '--class' => 'Database\Seeders\TenantDatabaseSeeder',
],
```

The central `DatabaseSeeder` calls `RolePermissionSeeder` and `TenantSeeder`, which operate on central tables (`permissions`, `roles`). Running it against a tenant database would fail because those tables don't exist there. `TenantDatabaseSeeder` only calls seeders that target tenant-specific tables.

### Migration Rules

Every tenant migration **must include a `tenant_id` column**:

```php
Schema::create('test_products', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('tenant_id')->nullable()->index();
    $table->string('name');
    // ...
    $table->softDeletes();
    $table->timestamps();
});
```

**Why `tenant_id` is required even in multi-db mode?**

- In single-db mode, `BelongsToTenant` (from `BaseModel`) uses `tenant_id` to scope all queries.
- In multi-db mode, `tenant_id` is set automatically as a safety measure — the real isolation comes from the separate database connection.
- This means the same migration and model work correctly in **both** strategies without modification.

---

## Creating Tenant-Scoped Models

### Step 1: Create the Model

All models **must** extend `BaseModel`. This provides `HasUuids`, `SoftDeletes`, `BelongsToTenant`, `Auditable`, `Filterable`, `Sortable`, and `InteractsWithMedia` automatically.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TestProduct extends BaseModel
{
    /** @use HasFactory<\Database\Factories\TestProductFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
        ];
    }
}
```

Only add traits not already provided by `BaseModel` (e.g. `HasFactory`).

### Step 2: Create the Migration

Place the migration in `database/migrations/tenant/`:

```bash
# Create the file manually or move it after generation
# Migration path: database/migrations/tenant/
```

Always include:
- `$table->uuid('id')->primary()` — UUIDs from `HasUuids`.
- `$table->string('tenant_id')->nullable()->index()` — For `BelongsToTenant` scoping.
- `$table->softDeletes()` — For `SoftDeletes`.
- `$table->timestamps()` — Standard timestamps.

### Step 3: Create the Factory

```php
<?php

namespace Database\Factories;

use App\Models\TestProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TestProduct> */
class TestProductFactory extends Factory
{
    protected $model = TestProduct::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(100, 99999),
            'stock' => fake()->numberBetween(0, 1000),
        ];
    }
}
```

---

## Tenant Lifecycle (Multi-DB Mode)

When `TENANCY_MULTI_DB=true`:

```
Tenant Created
    ├── Jobs\CreateDatabase   → CREATE DATABASE tenantacme
    ├── Jobs\MigrateDatabase  → Run database/migrations/tenant/*.php
    ├── SyncRolesPermissionsForNewTenant → Clone central roles/permissions
    └── (Manual) SyncMigrationsModal → Run migrations + TenantDatabaseSeeder + permission sync

Tenant Deleted
    └── Jobs\DeleteDatabase   → DROP DATABASE tenantacme
```

### Database Naming

Tenant databases follow the pattern configured in `config/tenancy.php`:

```
prefix + tenant_id + suffix
```

Default: `tenant` prefix, no suffix → `tenant<uuid>` (e.g. `tenantf47ac10b-58cc-4372-a567-0e02b2c3d479`).

---

## Docker Configuration

### MariaDB Init Script

The file `docker/mariadb/01-grant-tenant-dbs.sql` grants the application database user permission to create and drop tenant databases:

```sql
GRANT ALL PRIVILEGES ON `tenant%`.* TO 'wevetel'@'%';
FLUSH PRIVILEGES;
```

This runs automatically on MariaDB's first initialisation (via `/docker-entrypoint-initdb.d/`).

**Important**: If MariaDB data volume already exists, the init script won't re-run. To apply it:

```bash
# Option 1: Destroy volume and recreate
docker compose -f docker-compose.dev.yml down -v
docker compose -f docker-compose.dev.yml up -d --build

# Option 2: Run manually
docker exec wevetel_mariadb_dev mariadb -u root -prootsecret \
    -e "GRANT ALL PRIVILEGES ON \`tenant%\`.* TO 'wevetel'@'%'; FLUSH PRIVILEGES;"
```

### Docker Compose Volume Mount

The init script is mounted in `docker-compose.dev.yml`:

```yaml
mariadb:
  volumes:
    - mariadb_data_dev:/var/lib/mysql
    - ./docker/mariadb/01-grant-tenant-dbs.sql:/docker-entrypoint-initdb.d/01-grant-tenant-dbs.sql:ro
```

---

## Switching Between Strategies

### From Single-DB to Multi-DB

1. Set `TENANCY_MULTI_DB=true` in `.env`.
2. Ensure MariaDB grants are applied (see Docker section above).
3. Existing tenants will **not** automatically get their own databases — only newly created tenants will.
4. To migrate existing tenants, run:
   ```bash
   php artisan tenants:migrate
   ```
5. To seed existing tenant databases (uses `TenantDatabaseSeeder`, not the central `DatabaseSeeder`):
   ```bash
   php artisan tenants:seed
   ```
6. Alternatively, use the **Sync Migrations & Seed** button on the Tenants page to run migrations, seeders, and permission sync via the UI.

### From Multi-DB to Single-DB

1. Set `TENANCY_MULTI_DB=false` in `.env`.
2. Run `php artisan migrate` to create tenant tables in the central database.
3. Tenant databases will remain but won't be used. Clean them up manually if desired.

---

## Verification

### Single-DB Mode

```bash
# Tables from database/migrations/tenant/ appear in the central database
docker exec wevetel_app_dev php artisan migrate:fresh --seed

# Verify test_products table exists
docker exec wevetel_mariadb_dev mariadb -u wevetel -psecret wevetel \
    -e "SHOW TABLES LIKE 'test_products';"
```

### Multi-DB Mode

```bash
# Set TENANCY_MULTI_DB=true in .env first
docker exec wevetel_app_dev php artisan migrate:fresh --seed

# Create a test tenant (via tinker or UI)
# Verify a new database was created
docker exec wevetel_mariadb_dev mariadb -u root -prootsecret \
    -e "SHOW DATABASES LIKE 'tenant%';"
```

---

## Example: TestProduct

The `TestProduct` model is provided as a working example of a tenant-scoped model:

| Component | Path |
|-----------|------|
| Model | `app/Models/TestProduct.php` |
| Migration | `database/migrations/tenant/2026_02_23_000001_create_test_products_table.php` |
| Factory | `database/factories/TestProductFactory.php` |

It demonstrates:
- Extending `BaseModel` (inherits all standard traits).
- Only declaring `HasFactory` as an additional trait.
- Placing migrations in `database/migrations/tenant/`.
- Including `tenant_id` column for dual-strategy compatibility.

---

## Files Changed for Multi-DB Support

| File | Change |
|------|--------|
| `config/tenancy.php` | Conditional `DatabaseTenancyBootstrapper`, `database.multi_db` config key |
| `app/Models/Tenant.php` | Added `TenantWithDatabase` interface, `HasDatabase` trait |
| `app/Providers/TenancyServiceProvider.php` | Conditional DB job pipelines, `loadMigrationsFrom()` for single-db |
| `.env.example` | Added `TENANCY_MULTI_DB=false` |
| `docker-compose.dev.yml` | Mounted MariaDB init script |
| `docker/mariadb/01-grant-tenant-dbs.sql` | `GRANT ALL PRIVILEGES ON 'tenant%'.*` |

---

## Related Documentation

- [07-multi-tenancy.md](07-multi-tenancy.md) — Tenancy modes, route isolation, data scoping, and middleware configuration.
- [09-database-design.md](09-database-design.md) — Schema design, migration conventions, and UUID strategy.
- [13-docker-development-setup.md](13-docker-development-setup.md) — Docker Compose architecture and service configuration.
