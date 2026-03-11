# Folder Structure

## Root Directory

```
wevetel-starter-laravel/
|-- .env.example                Environment variable template
|-- .github/                    AI coding agent skill definitions
|-- AGENTS.md                   AI coding agent guidelines (Laravel Boost)
|-- artisan                     Laravel CLI entry point
|-- bootstrap/                  Application bootstrap
|-- boost.json                  Laravel Boost MCP server configuration
|-- composer.json               PHP dependency manifest
|-- config/                     Application configuration
|-- database/                   Migrations, seeders, factories
|-- docker/                     Docker build and runtime configuration
|-- docker-compose.dev.yml      Development Docker Compose definition
|-- Modules/                    Domain-specific feature modules
|-- modules_statuses.json       Module enable/disable state
|-- package.json                Node.js dependency manifest
|-- phpstan.neon                PHPStan static analysis configuration
|-- phpunit.xml                 PHPUnit/Pest test runner configuration
|-- pint.json                   Laravel Pint code formatter configuration
|-- public/                     Web-accessible document root
|-- resources/                  Frontend assets, views, CSS, JavaScript
|-- routes/                     HTTP and console route definitions
|-- storage/                    Application storage (logs, cache, uploads)
|-- stubs/                      Artisan generator stub templates
|-- tests/                      Automated test suite
|-- vendor/                     Composer dependencies (gitignored)
|-- vite.config.js              Vite build tool configuration
```

## Application Directory (`app/`)

### Actions (`app/Actions/`)

```
Actions/
|-- Fortify/
|   |-- CreateNewUser.php           User registration logic (tenant-aware)
|   |-- ResetUserPassword.php       Password reset handler
|-- SyncRolesPermissionsToTenantAction.php   Clones central roles/permissions to a tenant
```

`CreateNewUser` handles tenant association during registration by checking for `registering_tenant_id` in the session. `SyncRolesPermissionsToTenantAction` is invoked when a new tenant is created, copying central template roles and permissions with appropriate restrictions.

### Concerns (`app/Concerns/`)

```
Concerns/
|-- PasswordValidationRules.php      Shared password validation rule methods
|-- ProfileValidationRules.php       Shared name/email validation rule methods
|-- ResolveTenantRouteParam.php      Resolves {tenant} route parameter across tenancy modes
```

These traits are consumed by Livewire components and Fortify actions to maintain consistent validation logic.

### Console (`app/Console/`)

```
Console/
|-- (empty)
```

Console commands in `app/Console/Commands/` are auto-discovered by Laravel 12. No kernel registration is required.

### Events (`app/Events/`)

```
Events/
|-- (empty)
```

Application-level events are empty. Domain events reside in their respective modules.

### Exports (`app/Exports/`)

```
Exports/
|-- AccessControl/
    |-- PermissionsExport.php    Excel export query for permissions
    |-- RolesExport.php          Excel export query for roles with permissions
    |-- UsersExport.php          Excel export query for users with roles
```

All exports implement `FromQuery`, `WithHeadings`, `WithMapping`, and `ShouldAutoSize`.

### HTTP (`app/Http/`)

```
Http/
|-- Controllers/
|   |-- Controller.php                       Base controller with AuthorizesRequests
|   |-- SecureDownloadController.php         Signed URL file downloads (exports, media)
|   |-- Api/
|   |   |-- V1/
|   |       |-- Auth/
|   |           |-- TokenController.php      Sanctum token issue/revoke
|   |-- Auth/
|       |-- KeycloakController.php           Full Keycloak SSO integration (9 methods)
|-- Middleware/
|   |-- Authenticate.php                     Tenant-aware auth redirect
|   |-- CheckKeycloakBackchannelLogout.php   Keycloak session invalidation check
|   |-- EnsureCentralAccess.php              Blocks tenant users from central routes
|   |-- EnsureTenantAccess.php               Blocks central/foreign tenant users
|   |-- InitializeTenancyFromLivewireUpdate.php  Path-mode tenancy for Livewire AJAX
|-- Requests/
|   |-- Api/
|       |-- V1/
|           |-- Auth/
|               |-- LoginRequest.php         API login validation rules
|-- Resources/
|   |-- Api/
|       |-- V1/
|           |-- (empty, resources in modules)
|-- Responses/
    |-- LoginResponse.php                    Tenant-aware post-login redirect
    |-- LogoutResponse.php                   Keycloak-aware post-logout redirect
```

### Imports (`app/Imports/`)

```
Imports/
|-- AccessControl/
    |-- PermissionsImport.php    Row-by-row permission import with validation
    |-- RolesImport.php          Role import with optional permission sync
    |-- UsersImport.php          User import with optional role assignment
```

All imports implement `ToModel`, `WithHeadingRow`, `WithValidation`, and `SkipsOnFailure`.

### Jobs (`app/Jobs/`)

```
Jobs/
|-- AccessControl/
    |-- ExportPermissionsJob.php     Queued Excel export with signed URL caching
    |-- ExportRolesJob.php           Queued Excel export with signed URL caching
    |-- ExportUsersJob.php           Queued Excel export with signed URL caching
    |-- ImportPermissionsJob.php     Queued Excel import with result caching
    |-- ImportRolesJob.php           Queued Excel import with result caching
    |-- ImportUsersJob.php           Queued Excel import with result caching
```

Export jobs store files to the configured disk and cache signed download URLs. Import jobs process uploaded files and cache result summaries.

### Listeners (`app/Listeners/`)

```
Listeners/
|-- SyncRolesPermissionsForNewTenant.php    Triggered on TenantCreated event
```

### Livewire (`app/Livewire/`)

```
Livewire/
|-- Actions/
|   |-- Logout.php              Keycloak-aware logout action
|-- Auth/
|   |-- MagicLogin.php          Passwordless email link component
|-- Settings/
    |-- Appearance.php          Theme switcher (view-only)
    |-- DeleteUserForm.php      Account deletion with password confirmation
    |-- Password.php            Password change form
    |-- Profile.php             Name/email update with verification
    |-- TwoFactor.php           2FA enable/disable with QR code
    |-- TwoFactor/
        |-- RecoveryCodes.php   Recovery code display and regeneration
```

### Models (`app/Models/`)

```
Models/
|-- BaseModel.php       Abstract base: UUID, SoftDeletes, Auditable, BelongsToTenant, Media, Filtering
|-- Permission.php      Extends Spatie Permission: adds tenant_id, is_synced, central/tenant scopes
|-- Role.php            Extends Spatie Role: adds tenant_id, is_synced, central/tenant scopes
|-- Tenant.php          Extends Stancl BaseTenant: domains, users M2M, SoftDeletes, media
|-- User.php            Authenticatable: Sanctum, 2FA, roles, tenants M2M, tenant-scoped permissions
```

### Notifications (`app/Notifications/`)

```
Notifications/
|-- MagicLinkNotification.php    Custom magic login mail with branding
```

### Providers (`app/Providers/`)

```
Providers/
|-- AppServiceProvider.php          Core bindings, Gate config, Socialite, defaults
|-- FortifyServiceProvider.php      Auth actions, views, rate limiting, custom responses
|-- HorizonServiceProvider.php      Queue dashboard authorization
|-- TelescopeServiceProvider.php    Debug tool entry filtering
|-- TenancyServiceProvider.php      Tenancy events, routes, middleware priority
```

### Socialite (`app/Socialite/`)

```
Socialite/
|-- KeycloakProvider.php    Custom provider fixing Docker token exchange and userinfo
```

### Support (`app/Support/`)

```
Support/
|-- RecordLock.php              Cache-based optimistic locking (5 min TTL)
|-- Media/
    |-- RandomFileNamer.php     32-char hex filename generator for media uploads
```

## Modules Directory (`Modules/`)

### AccessControl (`Modules/AccessControl/`)

```
AccessControl/
|-- app/
|   |-- Events/                 PermissionRecordChanged, RoleRecordChanged, UserRecordChanged
|   |-- Http/
|   |   |-- Controllers/        UserController, RoleController, PermissionController (API)
|   |   |-- Requests/           Store/Update request classes for each resource
|   |   |-- Resources/          UserResource, RoleResource, PermissionResource (API)
|   |-- Livewire/
|   |   |-- Permissions/        Index, Create/Show/Edit/Delete/BulkDelete/ForceDelete/Restore/History/ImportExport/Sync modals
|   |   |-- Roles/              Index, Create/Show/Edit/Delete/BulkDelete/ForceDelete/Restore/History/ImportExport/Sync modals
|   |   |-- Users/              Index, Create/Show/Edit/Delete/BulkDelete/ForceDelete/Restore/History/ImportExport modals
|   |-- Providers/              AccessControlServiceProvider, EventServiceProvider, RouteServiceProvider
|   |-- Support/
|       |-- Media/              AccessControlPathGenerator
|-- config/                     Module configuration
|-- resources/views/            Blade templates for users, roles, permissions
|-- routes/                     Web and API route definitions
```

### OrganisationSetup (`Modules/OrganisationSetup/`)

```
OrganisationSetup/
|-- app/
|   |-- Events/                 TenantRecordChanged
|   |-- Exports/                TenantsExport
|   |-- Imports/                TenantsImport
|   |-- Jobs/                   ExportTenantsJob, ImportTenantsJob
|   |-- Livewire/
|   |   |-- Tenants/            Index, Create/Show/Edit/Delete/BulkDelete/ForceDelete/Restore/History/ImportExport/AssignDomain/TenantUser modals
|   |-- Providers/              OrganisationSetupServiceProvider, EventServiceProvider, RouteServiceProvider
|-- config/                     Module configuration
|-- resources/views/            Blade templates for tenants
|-- routes/                     Web and API route definitions
```

## Configuration (`config/`)

| File | Purpose |
|------|---------|
| `app.php` | Application name, environment, URL, timezone, locale |
| `audit.php` | Owen-IT audit driver, tracked events, queue settings |
| `auth.php` | Guards, user providers, password reset settings |
| `broadcasting.php` | Reverb/Pusher broadcasting configuration |
| `cache.php` | Redis cache store configuration |
| `database.php` | MariaDB connection, Redis connections |
| `debugbar.php` | Laravel Debugbar settings (dev only) |
| `excel.php` | Maatwebsite Excel chunk size, CSV/XLSX settings |
| `filesystems.php` | Local, public, S3, MinIO disk definitions |
| `fortify.php` | Authentication features, guard, home path |
| `horizon.php` | Queue supervisors, process limits, balancing |
| `livewire.php` | Component defaults, upload limits, temporary disk |
| `logging.php` | Daily log channel, deprecation logging |
| `magic-login.php` | Link expiration, usage limits, redirect |
| `mail.php` | Mailer transport configuration |
| `media-library.php` | Spatie media disk, max file size, file namer |
| `modules.php` | nwidart/laravel-modules paths and autoloading |
| `modules-livewire.php` | Module-to-Livewire component mapping |
| `permission.php` | Spatie permission models, teams, cache TTL |
| `purity.php` | Abbasudo filter operators and strategies |
| `queue.php` | Redis queue connection settings |
| `reverb.php` | WebSocket server bind address and port |
| `scribe.php` | API documentation generation rules |
| `services.php` | Keycloak, Postmark, Resend, SES credentials |
| `session.php` | Redis session driver, lifetime, encryption |
| `telescope.php` | Telescope storage and entry settings |
| `tenancy.php` | Tenancy mode, bootstrappers, central domains |

## Database (`database/`)

```
database/
|-- factories/
|   |-- UserFactory.php          User model factory with 2FA state
|-- migrations/                  22 migration files (see 09-database-design.md)
|   |-- tenant/                  Tenant-specific migrations (empty)
|-- seeders/
    |-- DatabaseSeeder.php           Orchestrates all central seeders
    |-- RolePermissionSeeder.php     Seeds access-control roles and permissions
    |-- TenantDatabaseSeeder.php     Root seeder for tenant databases (used by tenants:seed)
    |-- TenantSeeder.php             Seeds organisation-setup permissions (central)
```

## Docker (`docker/`)

```
docker/
|-- cron/
|   |-- laravel                  Cron entry for Laravel scheduler (every minute)
|-- keycloak-setup.sh            Keycloak realm and client provisioning script
|-- nginx/
|   |-- nginx.conf               Nginx reverse proxy configuration
|-- php/
    |-- Dockerfile.dev           PHP 8.4-FPM + Node 24 + extensions
    |-- entrypoint.sh            Container startup orchestration script
    |-- php.ini                  PHP runtime configuration
    |-- supervisord.conf         Supervisor process management (FPM, Horizon, Reverb)
```

## Resources (`resources/`)

```
resources/
|-- css/
|   |-- app.css                  Tailwind CSS 4 with Wevetel theme overrides
|-- js/
|   |-- app.js                   Laravel Echo + Reverb WebSocket setup
|-- views/
    |-- components/              Blade components (logo, menus, toast, head)
    |-- dashboard.blade.php      Main dashboard view
    |-- errors/                  Custom error pages (403, 404, 419, 429, 500, 503, tenant-not-found)
    |-- layouts/                 App and auth layout templates
    |-- livewire/                Livewire component views (auth forms, settings)
    |-- welcome.blade.php        Landing page
```

## Routes (`routes/`)

```
routes/
|-- api.php                      API v1 routes (Sanctum token endpoints)
|-- channels.php                 Broadcast channel authorization
|-- console.php                  Console command registration
|-- settings.php                 Settings page routes (included by web.php)
|-- tenant.php                   Tenant-scoped route definitions (subdomain + path)
|-- web.php                      Central web routes
```

## Tests (`tests/`)

```
tests/
|-- Feature/
|   |-- Auth/                    Authentication flow tests
|   |-- Settings/                Profile, password, account deletion tests
|   |-- Api/                     API endpoint tests
|   |-- DashboardTest.php        Dashboard access tests
|   |-- TenancyTest.php          Tenant CRUD and isolation tests
|   |-- TenantUserAccessTest.php Tenant access control tests
|   |-- AccessControl/           Module Livewire component tests
|   |-- OrganisationSetup/       Module Livewire component tests
|-- Unit/                        Unit tests
|-- Pest.php                     Pest configuration
|-- TestCase.php                 Base test class
```
