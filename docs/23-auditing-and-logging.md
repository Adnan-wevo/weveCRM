# Auditing and Logging

## Overview

The application tracks model changes through `owen-it/laravel-auditing` and provides multiple logging and debugging tools: Laravel Telescope for request inspection, Laravel Pail for real-time log streaming, and Laravel's built-in logging system with daily rotation.

## Model Auditing

### Package

`owen-it/laravel-auditing` v13 provides automatic audit trail recording for Eloquent model events.

### Configuration

`config/audit.php`:

| Setting | Value |
|---------|-------|
| Driver | `database` |
| Table | `audits` |
| Events | `created`, `updated`, `deleted`, `restored` |
| Threshold | `0` (unlimited audits per model) |
| Implementation | Custom `App\Models\Audit` model |

### Audits Table Schema

| Column | Type | Description |
|--------|------|-------------|
| `id` | `uuid` | Primary key |
| `user_type` | `string` | Auditing user's model class |
| `user_id` | `uuid` | Auditing user's ID |
| `event` | `string` | Event type (`created`, `updated`, `deleted`, `restored`) |
| `auditable_type` | `string` | Audited model's class |
| `auditable_id` | `uuid` | Audited model's ID |
| `old_values` | `json` | Previous attribute values |
| `new_values` | `json` | New attribute values |
| `url` | `text` | Request URL at the time of the event |
| `ip_address` | `string` | Client IP address |
| `user_agent` | `string` | Client user agent |
| `tags` | `string` | Optional tags |
| `created_at` | `timestamp` | Audit timestamp |
| `updated_at` | `timestamp` | Update timestamp |

### Enabling Auditing on Models

Models that should be audited implement the `Auditable` interface and use the `Auditable` trait:

```php
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class User extends Model implements AuditableContract
{
    use Auditable;
}
```

### Audit History in the UI

Module index views include a "History" modal component that displays the audit trail for individual records:

- **Event badge**: Color-coded badge indicating the event type (created, updated, deleted, restored).
- **Timestamp**: When the change occurred.
- **User**: Who made the change.
- **Old/New values**: Side-by-side comparison of changed attributes.

Audit history is loaded via the model's `audits()` relationship when the history modal is opened.

### Querying Audits

```php
// Get all audits for a model
$user->audits;

// Get audits for a specific event
$user->audits()->where('event', 'updated')->get();

// Get the latest audit
$user->audits()->latest()->first();
```

## Logging

### Configuration

`config/logging.php`:

| Setting | Value |
|---------|-------|
| Default channel | `daily` |
| Log level | `debug` |
| Retention | 14 days |
| Path | `storage/logs/laravel.log` |

### Available Channels

| Channel | Driver | Purpose |
|---------|--------|---------|
| `stack` | `stack` | Combines multiple channels |
| `single` | `single` | Single log file |
| `daily` | `daily` | Daily rotating log files |
| `stderr` | `monolog` | Standard error output (Docker logs) |
| `syslog` | `syslog` | System log |
| `errorlog` | `errorlog` | PHP error log |

### Logging in Application Code

```php
use Illuminate\Support\Facades\Log;

Log::info('User logged in', ['user_id' => $user->id]);
Log::warning('Rate limit approached', ['ip' => $request->ip()]);
Log::error('Import failed', ['file' => $filename, 'error' => $exception->getMessage()]);
```

### Docker Log Integration

All Supervisor-managed processes (PHP-FPM, Horizon, Reverb) log to `/dev/stdout` and `/dev/stderr`, making their output available via `docker compose logs`.

## Laravel Telescope

### Purpose

Telescope is a debug assistant that records detailed information about requests, queries, jobs, mail, notifications, cache operations, and more during development.

### Access

Dashboard: `http://localhost:8888/telescope`

### Recorded Data

| Watcher | Captures |
|---------|----------|
| Request | Incoming HTTP requests with headers, payload, response |
| Query | Database queries with execution time and bindings |
| Model | Model events (create, update, delete) |
| Job | Queued job dispatches and execution |
| Exception | Thrown exceptions with stack traces |
| Log | Log entries from all channels |
| Mail | Outgoing mail with content preview |
| Notification | Sent notifications |
| Cache | Cache hits, misses, and writes |
| Schedule | Scheduled task execution |
| Gate | Authorization gate checks |
| View | Rendered views with data |

### Configuration

`config/telescope.php`:

| Setting | Value |
|---------|-------|
| Enabled | `TELESCOPE_ENABLED` env var |
| Storage driver | `database` |
| Dark mode | Enabled |
| Path | `/telescope` |

Telescope is disabled in the test environment (`TELESCOPE_ENABLED=false` in `phpunit.xml`).

### Telescope Service Provider

`app/Providers/TelescopeServiceProvider.php`:

- Registers Telescope's migrations.
- Configures the authorization gate (development: all users allowed).
- Prunes entries older than the configured retention period.

## Laravel Pail

### Purpose

Real-time log tailing from the terminal with filtering and formatting.

### Usage

```bash
php artisan pail

# Filter by log level
php artisan pail --filter="error"

# Filter by message content
php artisan pail --filter="import"
```

### Docker Usage

```bash
docker compose -f docker-compose.dev.yml exec app php artisan pail
```

Pail reads from `storage/pail/` and displays formatted, colorized log entries in real-time. It is useful for monitoring application behavior during development without switching to the browser.

## Debugbar

### Configuration

`config/debugbar.php`:

| Setting | Value |
|---------|-------|
| Enabled | `DEBUGBAR_ENABLED` env var |
| Storage | `file` |
| Path | `storage/debugbar` |

When enabled, Debugbar adds a toolbar to the bottom of every HTML page showing:

- Request details.
- Database queries with timing.
- Route information.
- View rendering details.
- Memory usage.
- Session data.

### Enabling Debugbar

Set `DEBUGBAR_ENABLED=true` in `.env`. This should only be enabled in development.

## Monitoring Strategy Summary

| Tool | Scope | Access Method |
|------|-------|---------------|
| Audit Trail | Model-level changes | History modals in the UI |
| Laravel Logs | Application events | `storage/logs/`, Pail, Docker logs |
| Telescope | Full request lifecycle | `/telescope` browser dashboard |
| Horizon | Queue job monitoring | `/horizon` browser dashboard |
| Debugbar | Per-request profiling | Browser toolbar overlay |
| MinIO Console | File storage inspection | `http://localhost:9001` |
| Mailpit | Email inspection | `http://localhost:8025` |
