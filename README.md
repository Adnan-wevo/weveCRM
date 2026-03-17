<p align="center">
  <img src="public/branding/wevetel.png" width="200" alt="Wevetel">
</p>

<p align="center">
  <a href="https://www.php.net/releases/8.4/en.php"><img src="https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white" alt="PHP 8.4"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" alt="Laravel 12"></a>
  <a href="https://livewire.laravel.com"><img src="https://img.shields.io/badge/Livewire-4-FB70A9?logo=livewire&logoColor=white" alt="Livewire 4"></a>
  <a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4"></a>
  <a href="https://github.com/stancl/tenancy"><img src="https://img.shields.io/badge/Tenancy-3-6366F1" alt="stancl/tenancy 3"></a>
  <a href="https://spatie.be/docs/laravel-permission"><img src="https://img.shields.io/badge/Spatie_Permission-7-197593" alt="spatie/laravel-permission 7"></a>
  <a href="https://pestphp.com"><img src="https://img.shields.io/badge/Pest-4-F28D1A?logo=pest&logoColor=white" alt="Pest 4"></a>
  <a href="https://laravel.com/docs/horizon"><img src="https://img.shields.io/badge/Horizon-5-8B5CF6?logo=laravel&logoColor=white" alt="Horizon 5"></a>
  <a href="https://reverb.laravel.com"><img src="https://img.shields.io/badge/Reverb-1-FF2D20?logo=laravel&logoColor=white" alt="Reverb 1"></a>
  <a href="https://www.keycloak.org"><img src="https://img.shields.io/badge/Keycloak-26.1-4D4D4D?logo=keycloak&logoColor=white" alt="Keycloak 26.1"></a>
  <a href="https://mariadb.org"><img src="https://img.shields.io/badge/MariaDB-11.4-003545?logo=mariadb&logoColor=white" alt="MariaDB 11.4"></a>
  <a href="https://redis.io"><img src="https://img.shields.io/badge/Redis-7.4-DC382D?logo=redis&logoColor=white" alt="Redis 7.4"></a>
</p>

# Wevetel Starter Laravel

A production-grade **TALL Stack** development template featuring multi-tenant architecture, role-based access control, SSO integration, real-time broadcasting, and a modular domain-driven structure. Built on **Tailwind CSS**, **Alpine.js**, **Laravel**, and **Livewire** — designed for teams building SaaS applications that require tenant isolation, fine-grained permissions, and full-stack PHP interfaces.

---

## TALL Stack

This project is built on the **TALL stack** — a modern, full-stack PHP web development framework that enables building dynamic, responsive, and database-driven applications entirely in PHP, reducing the need for complex JavaScript SPAs.

| Component | Technology | Role |
|-----------|------------|------|
| **T** | [Tailwind CSS 4](https://tailwindcss.com) | Utility-first CSS framework for rapid UI design. Provides a consistent design system through composable utility classes, eliminating custom CSS. |
| **A** | [Alpine.js](https://alpinejs.dev) | Lightweight JavaScript framework for frontend interactivity. Handles dropdowns, modals, transitions, and client-side state where Livewire's server round-trip is unnecessary. |
| **L** | [Laravel 12](https://laravel.com) | Robust, mature PHP framework for backend development. Provides routing, authentication, queue management, broadcasting, ORM, and the full application infrastructure. |
| **L** | [Livewire 4](https://livewire.laravel.com) | Full-stack framework for building dynamic interfaces using PHP. Server-rendered reactive components handle form submissions, real-time updates, pagination, and data tables without writing JavaScript. |

### Why TALL Stack?

- **Single language** — PHP handles both backend logic and frontend reactivity through Livewire, eliminating the need for a separate JavaScript build pipeline or SPA framework.
- **Rapid development** — Tailwind's utility classes and Livewire's component model enable fast iteration without context-switching between languages.
- **Reduced complexity** — No REST/GraphQL API layer needed between frontend and backend; Livewire components communicate directly with Eloquent models and services.
- **Alpine.js for edge cases** — When client-side interactivity is needed (transitions, clipboard, local state), Alpine.js provides it without the overhead of React or Vue.
- **Flux UI integration** — The [Flux UI](https://fluxui.dev) component library provides pre-built, accessible TALL stack components (buttons, forms, modals, inputs) that maintain design consistency.

---

## Core Architecture

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.4, Laravel 12 |
| Frontend | Livewire 4, Flux UI Free, Tailwind CSS 4, Alpine.js |
| Authentication | Laravel Fortify, Keycloak SSO (OIDC), Magic Login (passwordless), Sanctum (API tokens) |
| Multi-Tenancy | stancl/tenancy (single-database or multi-database, three modes: single, subdomain, path) |
| Authorization | spatie/laravel-permission (tenant-scoped RBAC) |
| Queue & Workers | Laravel Horizon, Redis |
| Broadcasting | Laravel Reverb (WebSocket) |
| Database | MariaDB 11.4 |
| Object Storage | MinIO (S3-compatible) |
| Identity Provider | Keycloak 26.1 |
| Dev Tooling | Docker Compose, Vite 7, Pest 4, PHPStan, Pint |

## Key Features

- **Multi-Tenancy** -- Three configurable modes (single, subdomain, path) with single-database or multi-database isolation strategy (toggled via `TENANCY_MULTI_DB`) and automatic role and permission synchronization across tenants.
- **Access Control Module** -- Complete CRUD management for users, roles, and permissions with real-time table updates, audit history, import/export, and record locking.
- **Organisation Setup Module** -- Tenant lifecycle management with domain assignment, user association, and bulk operations.
- **Keycloak SSO** -- Full OAuth2/OIDC integration with account linking, back-channel logout, dedicated registration endpoint, and `CURLOPT_RESOLVE`-based networking for external Keycloak servers.
- **Magic Login** -- Passwordless email-link authentication with tenant context support.
- **Two-Factor Authentication** -- TOTP-based 2FA with QR code provisioning, recovery codes, and confirmation flow.
- **Real-Time Broadcasting** -- Reverb WebSocket server with tenant-scoped channels for live data table updates.
- **Audit Trail** -- Complete model change tracking with old/new value diffs, accessible through history modals.
- **Import/Export Pipeline** -- Queued Excel import and export with validation preview, progress polling, and signed download URLs.
- **API Layer** -- Versioned RESTful API (v1) with Sanctum token authentication and auto-generated Scribe documentation.
- **CRM Module (Core)** -- Contacts and Leads CRUD (Livewire) plus Form Builder skeleton under `Modules/CRM`.

### CRM Quick Usage

The CRM module is enabled via `modules_statuses.json` and exposes these routes after login:

- `/crm/contacts` (`crm.contacts.index`)
- `/crm/leads` (`crm.leads.index`)
- `/crm/forms` (`crm.forms.index`)

To access CRM sidebar links, grant `crm.view` permission to the user/role.

Run targeted CRM tests:

```bash
docker exec -i wevetel_app_dev php artisan test --compact tests/Feature/Crm/
```

---

## Template Philosophy

### What This Template Provides

- A fully wired development environment that starts with a single `docker compose` command.
- Pre-built authentication flows covering local credentials, SSO, passwordless, and API tokens.
- A modular architecture using `nwidart/laravel-modules` for domain separation.
- A tenant-scoped RBAC system that synchronizes permissions from central templates to each tenant.
- A complete set of Livewire CRUD interfaces with filtering, sorting, pagination, bulk operations, and audit history.

### What This Template Does Not Include

- Production deployment configurations (Kubernetes, cloud provisioning, load balancers).
- Payment or billing integrations.
- Customer-facing landing pages or marketing content.
- Pre-built business domain logic beyond the access control and organisation setup scaffolding.

### Development-Only Scope

This template is intended exclusively for development use. Docker Compose configurations, exposed ports, debug settings, and credential management are optimized for local development workflows. Production hardening is the responsibility of the implementing team.

---

## Quick Start

> **Do not clone this repository directly.** This is a template repository. Use GitHub's **"Use this template"** feature to create your own copy.

1. Click **"Use this template"** then **"Create a new repository"** at the top of this GitHub repository page.
2. Name your new repository and choose its visibility (public or private).
3. Clone your newly created repository:

```bash
git clone <your-new-repository-url> wevetel-starter
cd wevetel-starter
```

---

## Development Setup (Docker Compose Dev)

### Prerequisites

- Docker Engine 24+ and Docker Compose v2
- Git
- A `/etc/hosts` entry (optional, for subdomain tenancy mode):
  ```
  127.0.0.1  wevetel.test
  ```

### Start the Environment

```bash
cp .env.example .env

docker compose -f docker-compose.dev.yml up -d --build
```

The entrypoint script handles all remaining setup automatically:

1. Waits for MariaDB to become available.
2. Installs Composer and NPM dependencies.
3. Builds frontend assets.
4. Generates the application key.
5. Generates Reverb broadcasting credentials.
6. Runs database migrations.
7. Seeds the database (when `DB_SEED=true`).
8. Creates storage symlinks.
9. Generates API documentation.
10. Starts Supervisor (PHP-FPM, Horizon, Reverb).

### Access the Application

Once the containers are running:

| Service | URL | Default Credentials |
|---------|-----|---------------------|
| Application | `http://localhost:8000` | Register a new account |
| phpMyAdmin | `http://localhost:8081` | `wevetel` / `secret` |
| Mailpit | `http://localhost:8025` | No credentials required |
| MinIO Console | `http://localhost:9001` | `minioadmin` / `minioadmin` |
| Keycloak Admin | `http://localhost:8180` | `admin` / `admin` |
| Horizon Dashboard | `http://localhost:8000/horizon` | Requires `super-admin` role |
| Telescope Dashboard | `http://localhost:8000/telescope` | Requires `super-admin` role |
| API Documentation | `http://localhost:8000/docs` | Public |

### Creating the First Super Admin

After the containers are running and the database is seeded, create the first `super-admin` user:

```bash
docker exec -it wevetel_app_dev php artisan app:create-super-admin
```

The command will prompt for a name, email, and password. The created user will have the central `super-admin` role, which grants access to the Horizon and Telescope dashboards and bypasses all permission gates.

### Keycloak SSO Setup

#### Option A: Bundled Keycloak (Docker)

The `keycloak-setup` Docker Compose service automatically provisions a realm, client, and writes credentials to `.env` on first startup. No manual steps are needed — Keycloak SSO works out of the box after `docker compose up -d`.

#### Option B: External Keycloak Server

When using a Keycloak server outside Docker (e.g. on the host machine or a remote server):

1. Set the following in `.env`:

```dotenv
KEYCLOAK_BASE_URL=https://localhost              # Browser-facing URL
KEYCLOAK_INTERNAL_BASE_URL=https://host.docker.internal  # How the app container reaches Keycloak
KEYCLOAK_REALM=your-realm
KEYCLOAK_CLIENT_ID=your-client-id
KEYCLOAK_CLIENT_SECRET=your-client-secret
KEYCLOAK_REDIRECT_URI=http://wevetel.test:8002/auth/keycloak/callback
```

2. In Keycloak Admin Console, configure the client:
   - **Valid redirect URIs**: `http://wevetel.test:8002/auth/keycloak/callback`, `http://wevetel.test:8002/auth/keycloak/link/callback`
   - **Valid post logout redirect URIs**: `http://wevetel.test:8002/*`
   - **Backchannel logout URL**: Leave empty if Keycloak cannot reach the app
   - **User registration**: Enable in **Realm Settings → Login** for the registration button to work

3. Restart the app container: `docker compose -f docker-compose.dev.yml up -d app`

> The app uses `CURLOPT_RESOLVE` to route server-side requests (token exchange) to the internal URL while keeping the public hostname for SNI/SSL. See [Authentication System](docs/05-authentication-system.md#keycloak-sso) and [Docker Development Setup](docs/13-docker-development-setup.md#using-an-external-keycloak-server) for technical details.

### Stopping and Restarting

```bash
docker compose -f docker-compose.dev.yml down

docker compose -f docker-compose.dev.yml up -d

docker compose -f docker-compose.dev.yml down -v
```

The `-v` flag removes all named volumes (database, Redis, MinIO data). Omit it to preserve data between restarts.

---

## Services and Ports

| Service | Container Name | Image | Exposed Port(s) | Internal Hostname | Network |
|---------|---------------|-------|------------------|-------------------|---------|
| Application | `wevetel_app_dev` | Custom (PHP 8.4-FPM) | 9000 (internal) | `app` | internal |
| Nginx | `wevetel_nginx_dev` | nginx:1.27-alpine | `APP_PORT` (default: 8000) | `nginx` | internal, public |
| Vite Dev Server | `wevetel_vite_dev` | node:24-alpine | `VITE_PORT` (default: 5173) | `vite` | internal, public |
| MariaDB | `wevetel_mariadb_dev` | mariadb:11.4 | `DB_EXTERNAL_PORT` (default: 3307) | `mariadb` | internal |
| Redis | `wevetel_redis_dev` | redis:7.4-alpine | 6379 (internal) | `redis` | internal |
| phpMyAdmin | `wevetel_phpmyadmin_dev` | phpmyadmin:5.2 | `PMA_PORT` (default: 8081) | `phpmyadmin` | internal, public |
| Mailpit | `wevetel_mailpit_dev` | axllent/mailpit:latest | `MAILPIT_PORT` (default: 8025) | `mailpit` | internal, public |
| MinIO | `wevetel_minio_dev` | minio/minio:latest | `MINIO_PORT` (9000), `MINIO_CONSOLE_PORT` (9001) | `minio` | internal, public |
| MinIO Setup | `wevetel_minio_setup_dev` | minio/mc:latest | None | -- | internal |
| Keycloak DB | `wevetel_keycloak_db_dev` | postgres:16-alpine | None | `keycloak-db` | internal |
| Keycloak | `wevetel_keycloak_dev` | keycloak:26.1 | `KEYCLOAK_PORT` (default: 8180) | `keycloak` | internal, public |

---

## Environment Configuration

### Required Variables

| Variable | Purpose | Default |
|----------|---------|---------|
| `APP_URL` | Base application URL | `http://localhost:8000` |
| `APP_PORT` | Nginx exposed port | `8000` |
| `TENANCY_MODE` | Tenancy mode: `single`, `subdomain`, or `path` | `single` |
| `TENANCY_MULTI_DB` | Database strategy: `false` = shared DB, `true` = per-tenant DB | `false` |
| `TENANCY_CENTRAL_DOMAINS` | Comma-separated central domains (subdomain mode only) | `localhost,127.0.0.1` |
| `DB_DATABASE` | MariaDB database name | `wevetel` |
| `DB_USERNAME` | MariaDB username | `wevetel` |
| `DB_PASSWORD` | MariaDB password | `secret` |
| `DB_ROOT_PASSWORD` | MariaDB root password | `rootsecret` |
| `DB_EXTERNAL_PORT` | MariaDB host-exposed port | `3307` |
| `REDIS_HOST` | Redis hostname (overridden in Docker) | `127.0.0.1` |
| `QUEUE_CONNECTION` | Queue driver | `redis` |
| `BROADCAST_CONNECTION` | Broadcasting driver | `reverb` |
| `SESSION_DRIVER` | Session backend | `redis` |
| `CACHE_STORE` | Cache backend | `redis` |
| `MAIL_MAILER` | Mail driver | `log` |
| `MEDIA_DISK` | Media library storage disk | `minio` |
| `EXPORTS_DISK` | Export file storage disk | `local` |
| `DB_SEED` | Run seeders on container start | `true` |

### Keycloak Variables

| Variable | Purpose | Default |
|----------|---------|---------|
| `KEYCLOAK_BASE_URL` | Browser-facing Keycloak URL | `http://localhost:8180` |
| `KEYCLOAK_INTERNAL_BASE_URL` | Container-to-container Keycloak URL | (empty, set by Docker) |
| `KEYCLOAK_REALM` | Keycloak realm name | `wevetel` |
| `KEYCLOAK_CLIENT_ID` | OAuth client ID | (empty, generated by setup script) |
| `KEYCLOAK_CLIENT_SECRET` | OAuth client secret | (empty, generated by setup script) |

### MinIO Variables

| Variable | Purpose | Default |
|----------|---------|---------|
| `MINIO_ROOT_USER` | MinIO access key | `minioadmin` |
| `MINIO_ROOT_PASSWORD` | MinIO secret key | `minioadmin` |
| `MINIO_BUCKET` | Default storage bucket | `wevetel` |
| `MINIO_PORT` | MinIO API port | `9000` |
| `MINIO_CONSOLE_PORT` | MinIO web console port | `9001` |

### Reverb Variables

| Variable | Purpose | Default |
|----------|---------|---------|
| `REVERB_APP_ID` | Reverb application ID | (auto-generated on first start) |
| `REVERB_APP_KEY` | Reverb application key | (auto-generated on first start) |
| `REVERB_APP_SECRET` | Reverb application secret | (auto-generated on first start) |
| `REVERB_HOST` | Reverb host | `localhost` |
| `REVERB_PORT` | Reverb port | `8088` |

---

## Commands Reference (Development)

### Artisan (inside container)

```bash
docker exec -it wevetel_app_dev artisan <command>
```

The `artisan` wrapper runs commands as UID 1000 so generated files are owned by the host user.

| Command | Purpose |
|---------|---------|
| `artisan migrate` | Run pending migrations |
| `artisan migrate:fresh --seed` | Reset database and seed |
| `artisan db:seed` | Run all seeders |
| `artisan db:seed --class=RolePermissionSeeder` | Seed roles and permissions only |
| `artisan db:seed --class=TenantSeeder` | Seed tenant permissions only |
| `artisan optimize:clear` | Clear all caches (config, route, view, event) |
| `artisan horizon` | Start Horizon queue worker (managed by Supervisor) |
| `artisan horizon:terminate` | Gracefully terminate Horizon |
| `artisan reverb:start` | Start Reverb WebSocket server (managed by Supervisor) |
| `artisan scribe:generate` | Regenerate API documentation |
| `artisan telescope:clear` | Clear Telescope entries |
| `artisan schedule:run` | Execute scheduled tasks (handled by cron) |
| `artisan test --compact` | Run test suite |
| `artisan test --compact --filter=TestName` | Run specific test |
| `artisan pail` | Tail application logs in real time |

### Docker Compose

```bash
docker compose -f docker-compose.dev.yml up -d --build

docker compose -f docker-compose.dev.yml down

docker compose -f docker-compose.dev.yml down -v

docker compose -f docker-compose.dev.yml logs -f app

docker compose -f docker-compose.dev.yml exec app bash
```

### Code Quality

```bash
docker exec -it wevetel_app_dev bash -c "vendor/bin/pint"

docker exec -it wevetel_app_dev bash -c "vendor/bin/phpstan analyse --memory-limit=2G"

docker exec -it wevetel_app_dev bash -c "vendor/bin/pest"
```

---

## Repository Structure Overview

```
wevetel-starter-laravel/
|-- app/
|   |-- Actions/           Fortify auth actions, tenant sync action
|   |-- Concerns/          Shared traits (validation rules, tenant resolution)
|   |-- Console/           Console kernel (empty, auto-discovered)
|   |-- Events/            Application events (empty, module events used)
|   |-- Exports/           Excel export classes (users, roles, permissions)
|   |-- Http/              Controllers, middleware, requests, responses, resources
|   |-- Imports/           Excel import classes (users, roles, permissions)
|   |-- Jobs/              Queued import/export jobs
|   |-- Listeners/         Event listeners (tenant role sync)
|   |-- Livewire/          Livewire components (auth, settings)
|   |-- Models/            Eloquent models (User, Tenant, Role, Permission, BaseModel)
|   |-- Notifications/     Mail notifications (magic login)
|   |-- Providers/         Service providers (App, Fortify, Horizon, Telescope, Tenancy)
|   |-- Socialite/         Custom Keycloak Socialite provider
|   |-- Support/           Utilities (record locking, media naming)
|-- bootstrap/             Application bootstrap and provider registration
|-- config/                Configuration files
|-- database/
|   |-- factories/         Model factories
|   |-- migrations/        Database migrations (22 files)
|   |-- seeders/           Database seeders (roles, permissions, tenants)
|-- docker/                Docker configuration files
|   |-- cron/              Cron job definitions
|   |-- nginx/             Nginx configuration
|   |-- php/               PHP-FPM, Supervisor, entrypoint, php.ini
|-- Modules/
|   |-- AccessControl/     Users, roles, permissions management module
|   |-- OrganisationSetup/ Tenant lifecycle management module
|-- public/                Public web root
|-- resources/
|   |-- css/               Tailwind CSS with Wevetel theme
|   |-- js/                Echo/Reverb broadcasting setup
|   |-- views/             Blade templates (layouts, auth, settings, errors, components)
|-- routes/                Route definitions (web, api, tenant, settings, console, channels)
|-- stubs/                 Artisan generator stubs
|-- tests/                 Pest test suite (Feature and Unit)
```

---

## Documentation Index

| Document | Description |
|----------|-------------|
| [docs/01-architecture-overview.md](docs/01-architecture-overview.md) | System architecture, design principles, and technology stack rationale. |
| [docs/02-request-lifecycle.md](docs/02-request-lifecycle.md) | HTTP request flow from entry to response, middleware pipeline, and provider boot sequence. |
| [docs/03-folder-structure.md](docs/03-folder-structure.md) | Detailed breakdown of every directory and key file in the repository. |
| [docs/04-module-breakdown.md](docs/04-module-breakdown.md) | AccessControl and OrganisationSetup module architecture, components, and extension patterns. |
| [docs/05-authentication-system.md](docs/05-authentication-system.md) | Fortify, Keycloak SSO, Magic Login, and Sanctum authentication flows. |
| [docs/06-authorization-and-policies.md](docs/06-authorization-and-policies.md) | Tenant-scoped RBAC, permission naming, gate configuration, and middleware guards. |
| [docs/07-multi-tenancy.md](docs/07-multi-tenancy.md) | Tenancy modes, tenant resolution, route isolation, and data scoping. |
| [docs/08-api-architecture.md](docs/08-api-architecture.md) | API versioning, Sanctum token management, resource structure, and Scribe documentation. |
| [docs/09-database-design.md](docs/09-database-design.md) | Schema design, migration history, model relationships, and UUID strategy. |
| [docs/10-service-layer.md](docs/10-service-layer.md) | Actions, support utilities, record locking, and media handling. |
| [docs/11-event-job-queue-system.md](docs/11-event-job-queue-system.md) | Horizon configuration, broadcast events, queued jobs, and the import/export pipeline. |
| [docs/12-frontend-architecture.md](docs/12-frontend-architecture.md) | Livewire components, Flux UI, Tailwind theme, Vite configuration, and Alpine.js integration. |
| [docs/13-docker-development-setup.md](docs/13-docker-development-setup.md) | Container architecture, Dockerfile, Supervisor, Nginx, and entrypoint orchestration. |
| [docs/14-testing-strategy.md](docs/14-testing-strategy.md) | Pest test suite structure, coverage areas, and testing conventions. |
| [docs/15-security-considerations.md](docs/15-security-considerations.md) | Authentication security, CSRF handling, signed URLs, password policies, and data protection. |
| [docs/16-third-party-packages.md](docs/16-third-party-packages.md) | Complete dependency inventory with version, purpose, and configuration reference. |
| [docs/17-coding-standards.md](docs/17-coding-standards.md) | PHP conventions, Pint configuration, PHPStan rules, and naming patterns. |
| [docs/18-performance-considerations.md](docs/18-performance-considerations.md) | Caching strategy, OPcache, query optimization, and queue architecture. |
| [docs/19-troubleshooting-guide.md](docs/19-troubleshooting-guide.md) | Common issues, debugging tools, and resolution procedures. |
| [docs/20-broadcasting-and-realtime.md](docs/20-broadcasting-and-realtime.md) | Reverb WebSocket server, Echo client, tenant-scoped channels, and event broadcasting. |
| [docs/21-notifications-and-mail.md](docs/21-notifications-and-mail.md) | Mail configuration, notification classes, and magic login email flow. |
| [docs/22-files-and-storage.md](docs/22-files-and-storage.md) | Filesystem disks, MinIO integration, media library, and secure downloads. |
| [docs/23-auditing-and-logging.md](docs/23-auditing-and-logging.md) | Owen-IT audit trail, Telescope, Pail, and logging configuration. |
| [docs/24-impersonation.md](docs/24-impersonation.md) | Super-admin impersonation feature, audit log, Livewire modal, and leave-session flow. |
| [docs/25-multi-database-tenancy.md](docs/25-multi-database-tenancy.md) | Multi-database tenancy strategy, env toggle, tenant lifecycle, migration setup, and Docker configuration. |

---

## Ownership and Disclaimer

**Wevetel** is a product of **Wevo System Sdn Bhd**. This repository and all associated source code, documentation, and assets are the intellectual property of Wevo System Sdn Bhd. All rights reserved.

This software is intended strictly for internal use within the organisation. Unauthorised reproduction, distribution, or disclosure of any part of this repository to external parties is prohibited without prior written consent from Wevo System Sdn Bhd.

The third-party open-source packages included in this project are subject to their respective licenses. Wevo System Sdn Bhd does not claim ownership of these packages.

## Maintainer

**Department of Research and Development (R&D)**
Wevo System Sdn Bhd

Maintained by **Muhamad Said Nizamuddin bin Nadim**

---

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
