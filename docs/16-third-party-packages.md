# Third-Party Packages

## Overview

This document catalogues all third-party dependencies used by the application. Dependencies are split between PHP packages (managed by Composer) and JavaScript packages (managed by NPM).

## PHP Dependencies (Production)

| Package | Version Constraint | Purpose |
|---------|--------------------|---------|
| `php` | `^8.4` | Runtime requirement |
| `laravel/framework` | `^12.0` | Core framework |
| `laravel/fortify` | `^1.24` | Headless authentication backend (login, registration, 2FA, password reset) |
| `laravel/horizon` | `^5.31` | Redis queue dashboard and supervisor |
| `laravel/reverb` | `@beta` | WebSocket server for real-time broadcasting |
| `laravel/sanctum` | `^4.0` | API token authentication |
| `laravel/socialite` | `^5.16` | OAuth authentication (Keycloak SSO) |
| `laravel/telescope` | `^5.7` | Debug assistant and request inspector |
| `laravel/tinker` | `^2.10.1` | REPL for Laravel |
| `livewire/flux` | `^2.1` | Official Livewire component library (Free edition) |
| `livewire/livewire` | `^3.6` | Full-stack reactive component framework |
| `stancl/tenancy` | `^3.9` | Multi-tenancy (single-database, subdomain/path/single modes) |
| `spatie/laravel-permission` | `^6.16` | Role-based access control with tenant scoping |
| `spatie/laravel-medialibrary` | `^11.12` | File attachment and media management |
| `owen-it/laravel-auditing` | `^13.6.12` | Model change audit trail |
| `maatwebsite/excel` | `^3.1` | Excel/CSV import and export |
| `nwidart/laravel-modules` | `^12.0` | Modular application architecture |
| `knuckleswtf/scribe` | `^4.39` | Automated API documentation generation |
| `grovyle/laravel-magic-login` | `^2.1` | Passwordless authentication via email magic links |
| `abbasudo/laravel-purity` | `^3.5` | Dynamic query filtering and sorting |

## PHP Dependencies (Development)

| Package | Version Constraint | Purpose |
|---------|--------------------|---------|
| `fakerphp/faker` | `^1.23` | Test data generation |
| `laravel/boost` | `^2.1` | AI assistant MCP tools |
| `laravel/mcp` | `^0.1.1` | MCP server integration |
| `laravel/pail` | `^1.2.2` | Real-time log viewer |
| `laravel/pint` | `^1.13` | Code style formatter (PSR-12 / Laravel preset) |
| `laravel/sail` | `^1.41` | Docker development environment alternative |
| `larastan/larastan` | `^3.4` | PHPStan extension for Laravel |
| `mockery/mockery` | `^1.6` | Mock object framework for testing |
| `nunomaduro/collision` | `^8.1` | Error reporting for CLI |
| `pestphp/pest` | `^3.8` | Testing framework (BDD-style) |
| `phpunit/phpunit` | `^11.5.3` | Test runner |

## JavaScript Dependencies (Production)

| Package | Version | Purpose |
|---------|---------|---------|
| `@tailwindcss/vite` | `^4.1.8` | Tailwind CSS Vite plugin |
| `tailwindcss` | `^4.1.8` | Utility-first CSS framework |
| `axios` | `^1.9.0` | HTTP client |
| `laravel-echo` | `^2.1.4` | WebSocket client for Laravel broadcasting |
| `pusher-js` | `^8.4.0` | WebSocket protocol (used by Echo with Reverb) |

## JavaScript Dependencies (Development)

| Package | Version | Purpose |
|---------|---------|---------|
| `@tailwindcss/vite` | `^4.1.8` | Tailwind CSS Vite integration |
| `concurrently` | `^9.1.2` | Run multiple processes in parallel |
| `laravel-vite-plugin` | `^1.2.0` | Vite integration for Laravel |
| `vite` | `^7.0.0` | Frontend build tool and dev server |

## Key Package Integration Notes

### stancl/tenancy

- Single-database strategy (`DatabaseTenancyBootstrapper` is disabled).
- Active bootstrappers: `CacheTenancyBootstrapper`, `FilesystemTenancyBootstrapper`, `QueueTenancyBootstrapper`.
- Supports three tenancy modes: `single`, `subdomain`, `path`.
- Custom route parameters via `ResolveTenantRouteParam` trait.
- Persistent Livewire middleware configured in `config/livewire.php`.

### spatie/laravel-permission

- Roles and permissions scoped by `tenant_id`.
- Custom `User` model overrides for tenant-aware permission checks.
- Cache prefix configured to avoid cross-tenant collisions.
- Teams feature enabled (`teams_permission` config key).

### spatie/laravel-medialibrary

- Uses `RandomFileNamer` (custom) for non-predictable file names.
- Uses `AccessControlPathGenerator` (custom) for tenant-scoped storage paths.
- Configured for S3-compatible storage (MinIO in development).

### owen-it/laravel-auditing

- Database driver stores audit records in `audits` table.
- Tracks `created`, `updated`, `deleted`, `restored` events.
- Stores old and new values as JSON.
- Custom `Audit` model extends the package's base model.
- Audit history displayed in modal components within the modules.

### maatwebsite/excel

- `WithChunkReading` for memory-efficient large file imports.
- Queued imports via `ShouldQueue` on import classes.
- Progress tracking.
- Custom import/export classes per model in the modules.

### nwidart/laravel-modules

- Two modules: `AccessControl` and `OrganisationSetup`.
- Module status tracked in `modules_statuses.json`.
- Each module has its own routes, controllers, models, views, tests, config.
- Livewire components registered via `modules-livewire.php` config.

### knuckleswtf/scribe

- Generates OpenAPI/Swagger documentation.
- Published at `/docs` endpoint.
- Configured in `config/scribe.php` with Bearer token auth strategy.
- Auto-generated during container startup via entrypoint script.

### laravel/telescope

- Access restricted to users with the `super-admin` Spatie role via the `viewTelescope` gate in `TelescopeServiceProvider`.
- In non-local environments, only failure-type entries are recorded (exceptions, failed requests, failed jobs, scheduled tasks, monitored tags).
- Sensitive headers and parameters are stripped before storage.
- Disabled in the test environment via `TELESCOPE_ENABLED=false`.

### laravel/horizon

- Access restricted to users with the `super-admin` Spatie role via the `viewHorizon` gate in `HorizonServiceProvider`.
- Provides real-time queue metrics, job throughput, and failed job management via the `/horizon` dashboard.
- Queue supervisors, worker counts, and balancing strategies are configured in `config/horizon.php`.

### abbasudo/laravel-purity

- Provides dynamic filtering and sorting on Eloquent queries.
- Over 20 filter operators (equals, contains, starts_with, etc.).
- Applied via `Filterable` trait on models.
- Filter UI renders operator dropdowns in index views.

### grovyle/laravel-magic-login

- Generates time-limited, single-use login tokens.
- Sends `MagicLinkNotification` via email.
- Custom Livewire `MagicLogin` component handles the request flow.
- Configured in `config/magic-login.php`.

## Updating Dependencies

### PHP Dependencies

```bash
composer update
composer audit
```

### JavaScript Dependencies

```bash
npm update
npm audit
```

### Version Constraints

- Production PHP packages use caret (`^`) version constraints for minor/patch flexibility.
- Development packages follow the same convention.
- The `laravel/reverb` package uses `@beta` stability flag.
