# Performance Considerations

## Overview

This document describes the performance-related configurations and patterns used throughout the application. These settings are tuned for the Docker Compose development environment and may require adjustment for production deployments.

## Caching

### Redis

Redis 7.4 serves as the unified cache, session, queue, and broadcasting backend.

| Purpose | Connection | Config Key |
|---------|-----------|------------|
| Application cache | `default` | `CACHE_STORE=redis` |
| Session storage | `default` | `SESSION_DRIVER=database` (default, or `redis`) |
| Queue broker | `default` | `QUEUE_CONNECTION=redis` |
| Broadcasting pub/sub | `default` | Used by Reverb |

### Cache Tenant Isolation

The `CacheTenancyBootstrapper` prefixes all cache keys with the current tenant identifier. This ensures that cached data is never shared across tenants while using a single Redis instance.

### OPcache

PHP OPcache is enabled in `docker/php/php.ini`:

| Setting | Value |
|---------|-------|
| `opcache.enable` | `1` |
| `opcache.memory_consumption` | `128` MB |
| `opcache.max_accelerated_files` | `10000` |
| `opcache.validate_timestamps` | `1` (development) |

For production, set `opcache.validate_timestamps=0` and use `php artisan optimize` to precompile configuration and routes.

## Database Optimization

### Eager Loading

The application uses eager loading to prevent N+1 query problems. Livewire components that display related data (users with roles, roles with permissions) load relationships before rendering.

```php
User::with(['roles', 'tenant'])->paginate();
```

### Query Scoping with Purity

The Purity package generates efficient SQL queries for dynamic filtering and sorting. Filters are applied at the query builder level before pagination, ensuring that only matching records are retrieved from the database.

### Soft Deletes

Soft-deleted records remain in the database but are excluded from default queries via global scopes. The `withTrashed()` scope is applied only when viewing the trash tab, keeping default queries lean.

### UUIDs

All models use UUID primary keys. While UUIDs prevent sequential ID guessing, they have implications for index performance. The application uses `char(36)` columns with standard B-tree indexes.

### Indexing

Migrations define indexes on:

- Foreign keys (`tenant_id`, `user_id`).
- Frequently filtered columns.
- Unique constraints (email, domain combinations).

## Queue Performance

### Horizon Configuration

Horizon manages Redis queues with the following supervisor configuration:

| Setting | Value |
|---------|-------|
| Connection | `redis` |
| Queue | `default` |
| Workers | `3` |
| Max Processes | `3` |
| Max Time | `0` (unlimited) |
| Max Jobs | `0` (unlimited) |
| Memory Limit | `128` MB |
| Tries | `3` (default) |
| Timeout | `90` seconds |
| Balance | `auto` |

Horizon auto-balances workers across queues based on throughput. The dashboard at `/horizon` provides real-time metrics on job throughput, runtime, and failure rates.

### Queued Operations

The following operations run asynchronously via queues:

- Excel/CSV imports (chunked reading with progress tracking).
- Excel/CSV exports (chunked writing with progress tracking).
- Import/export completion notifications.

### Job Retry and Failure

- Failed jobs are stored in the `failed_jobs` table.
- Default retry count: 3 attempts.
- Horizon provides retry and delete functionality from the dashboard.

## Asset Optimization

### Vite Build

Production assets are compiled with Vite:

```bash
npm run build
```

This produces:

- Minified JavaScript bundles.
- Minified CSS with Tailwind CSS tree-shaking (unused utility classes are removed).
- Content-hashed filenames for cache busting.
- Asset manifest for Laravel's `@vite` Blade directive.

### Tailwind CSS

Tailwind CSS 4 uses the Vite plugin for JIT compilation. Only utility classes actually used in Blade templates, Livewire components, and JavaScript files are included in the production CSS bundle.

## WebSocket Performance

### Reverb

Reverb runs as a persistent process managed by Supervisor. Configuration:

| Setting | Value |
|---------|-------|
| Host | `0.0.0.0` |
| Port | `8080` |
| Max Request Size | `10000` KB |

Reverb handles WebSocket connections for real-time broadcasting. Events use `ShouldBroadcastNow` to bypass the queue and deliver immediately.

### Nginx WebSocket Proxy

Nginx is configured to proxy WebSocket connections (`/app/*`) to Reverb with proper `Upgrade` and `Connection` headers. The proxy timeout is set to `3600` seconds for long-lived connections.

## Memory Management

### PHP Memory Limit

```ini
memory_limit = 256M
```

This limit applies per PHP-FPM worker process. Chunked imports and exports use `WithChunkReading` to process large files within this memory budget.

### Horizon Worker Memory

Each Horizon worker has a 128 MB memory limit. Workers are restarted automatically if they exceed this threshold.

## Request Lifecycle Optimizations

### Middleware Stack

The middleware stack is ordered to fail fast:

1. Maintenance mode check.
2. CSRF validation.
3. Tenant identification.
4. Authentication.
5. Authorization.

Unauthenticated or unauthorized requests are rejected before reaching application logic.

### Livewire Dehydration

Livewire only sends the minimum required data between the server and browser. Component state is dehydrated to a compact format, reducing payload size for subsequent interactions.

## Monitoring and Profiling

### Telescope

Laravel Telescope (enabled in development) records:

- Incoming requests and responses.
- Database queries with execution time.
- Cache operations.
- Queue jobs and their runtime.
- Log entries.
- Mail messages.
- Scheduled tasks.

Telescope is disabled in the test environment (`TELESCOPE_ENABLED=false`).

### Horizon Dashboard

Horizon provides real-time monitoring at `/horizon`:

- Current queue throughput.
- Job runtime distribution.
- Failed jobs with stack traces.
- Worker status and process counts.

### Laravel Pail

Real-time log tailing via:

```bash
php artisan pail
```

## Optimization Commands

### Development

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild route and config caches
php artisan optimize
```

### Pre-Deployment Checklist

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache

# Build frontend assets
npm run build

# Restart Horizon workers
php artisan horizon:terminate
```

Note: These commands are not required during development. The Docker entrypoint script handles necessary initialization automatically.
