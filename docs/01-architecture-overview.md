# Architecture Overview

## Design Philosophy

This application follows a modular monolith architecture built on Laravel 12. Domain logic is separated into discrete modules using `nwidart/laravel-modules`, while shared infrastructure (authentication, tenancy, media handling) resides in the core application layer. The frontend is rendered server-side using Livewire 4 with Flux UI components, eliminating the need for a separate JavaScript framework build pipeline for application interfaces.

## System Architecture Diagram

```
                                    +------------------+
                                    |    Browser       |
                                    +--------+---------+
                                             |
                                    +--------v---------+
                                    |   Nginx (proxy)  |
                                    +---+---------+----+
                                        |         |
                          +-------------+    +----+-----------+
                          |                  |                |
                 +--------v------+  +--------v------+  +-----v--------+
                 |  PHP-FPM      |  |  Vite (HMR)   |  |  Reverb (WS) |
                 |  (Laravel)    |  |  (dev only)    |  |  (WebSocket) |
                 +---+---+---+--+  +----------------+  +--------------+
                     |   |   |
          +----------+   |   +----------+
          |              |              |
   +------v---+  +------v---+  +-------v----+
   | MariaDB  |  |  Redis   |  |   MinIO    |
   | (data)   |  | (cache/  |  | (S3 files) |
   |          |  |  queue/  |  |            |
   |          |  |  session)|  |            |
   +----------+  +----------+  +------------+

   +--------------+
   |  Keycloak    |
   |  (SSO/OIDC)  |
   |  + Postgres  |
   +--------------+
```

## Core Layers

### HTTP Layer

All HTTP traffic enters through Nginx, which serves as a reverse proxy routing requests to three backend services:

- **PHP-FPM** for Laravel application requests.
- **Vite** for frontend asset hot module replacement during development.
- **Reverb** for WebSocket connections used by Laravel Echo.

### Application Layer

The Laravel application is organized into the following structural layers:

| Layer | Location | Responsibility |
|-------|----------|----------------|
| Controllers | `app/Http/Controllers/` | HTTP request handling, delegation to services |
| Livewire Components | `app/Livewire/`, `Modules/*/app/Livewire/` | Server-rendered reactive UI components |
| Actions | `app/Actions/` | Discrete, reusable business operations |
| Models | `app/Models/` | Eloquent ORM, relationships, scopes, casts |
| Events | `Modules/*/app/Events/` | Domain event broadcasting |
| Jobs | `app/Jobs/`, `Modules/*/app/Jobs/` | Queued background processing |
| Listeners | `app/Listeners/` | Event reaction handlers |
| Middleware | `app/Http/Middleware/` | Request filtering and tenant context |
| Providers | `app/Providers/` | Service container bindings and boot logic |

### Data Layer

- **MariaDB 11.4** serves as the primary relational database. All tenants share a single database with tenant isolation enforced at the query level via `tenant_id` columns.
- **Redis 7.4** provides caching, session storage, queue backend, and broadcasting pub/sub.
- **MinIO** provides S3-compatible object storage for file uploads, exports, and media.

### Module Layer

Domain-specific functionality is encapsulated in Laravel Modules:

| Module | Namespace | Domain |
|--------|-----------|--------|
| AccessControl | `Modules\AccessControl` | User, role, and permission lifecycle management |
| OrganisationSetup | `Modules\OrganisationSetup` | Tenant creation, domain assignment, user association |

Each module contains its own controllers, Livewire components, events, jobs, exports, imports, views, routes, and service providers. Modules register themselves via `nwidart/laravel-modules` and are enabled through `modules_statuses.json`.

## Technology Stack Rationale

### TALL Stack

This application is built on the **TALL stack** (Tailwind CSS, Alpine.js, Laravel, Livewire) — a modern, full-stack PHP web development framework that enables building dynamic, responsive, and database-driven applications entirely in PHP, reducing the need for complex JavaScript SPAs.

| Component | Version | Role |
|-----------|---------|------|
| **Tailwind CSS** | 4 | Utility-first CSS framework for rapid, consistent UI design |
| **Alpine.js** | 3 | Lightweight JavaScript for client-side interactivity (dropdowns, modals, transitions) |
| **Laravel** | 12 | Full backend framework: routing, auth, queues, broadcasting, ORM |
| **Livewire** | 4 | Server-rendered reactive PHP components for dynamic interfaces |

The TALL stack allows the entire application to be built in PHP with minimal JavaScript, using Alpine.js only where client-side state is necessary (e.g., transitions, clipboard operations). Livewire handles all server communication, form handling, and real-time updates without requiring a REST/GraphQL API layer.

### Laravel 12

Selected as the foundation for its mature ecosystem, built-in support for queues, broadcasting, authentication, and its streamlined configuration model introduced in Laravel 11+.

### Livewire 4 with Flux UI

Eliminates the complexity of maintaining a separate JavaScript SPA. Server-rendered components handle reactivity, form handling, and real-time updates. Flux UI provides a consistent, accessible TALL stack component library.

### stancl/tenancy (Single-Database)

The single-database approach was chosen over database-per-tenant to simplify development workflows, reduce operational overhead, and allow cross-tenant queries from the central context. Tenant isolation is enforced through `tenant_id` scoping on models and tenant-aware middleware.

### Spatie Permission with Tenant Scoping

Roles and permissions carry a `tenant_id` column, enabling the same permission names to exist independently across tenants. The `User` model overrides Spatie's default permission resolution to filter by the current tenant context.

### Keycloak

Provides enterprise-grade SSO capabilities including OIDC, SAML, user federation, and social login. The Keycloak instance runs alongside the application in Docker for development, with a setup script that provisions the realm and client automatically.

### Horizon and Reverb

Horizon provides a dashboard for monitoring Redis-backed queue workers. Reverb provides a first-party WebSocket server for real-time broadcasting without external service dependencies.

## Request Flow Summary

1. Browser sends HTTP request to Nginx on `APP_PORT`.
2. Nginx routes to PHP-FPM (application), Vite (assets), or Reverb (WebSocket).
3. Laravel middleware pipeline processes the request: session, CSRF, tenant initialization, authentication, authorization.
4. Route resolves to a controller action or Livewire full-page component.
5. Business logic executes through models, actions, and services.
6. Response rendered via Blade/Livewire and returned through Nginx.
7. Background operations dispatched to Horizon queue workers.
8. Real-time updates broadcast via Reverb to connected Echo clients.

## Extension Points

- **New modules**: Create via `php artisan module:make ModuleName`. Follow the AccessControl module structure.
- **New models**: Extend `BaseModel` for automatic UUID, soft-delete, audit, media, filtering, sorting, and tenant scoping.
- **New permissions**: Add to the appropriate seeder and run `php artisan db:seed --class=RolePermissionSeeder`.
- **New API endpoints**: Add versioned routes under `Modules/*/routes/api.php` with Sanctum middleware.
- **New broadcast events**: Implement `ShouldBroadcastNow` with tenant-scoped channel names.
