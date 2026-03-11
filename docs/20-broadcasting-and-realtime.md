# Broadcasting and Real-Time

## Overview

The application uses Laravel Reverb as a first-party WebSocket server for real-time event broadcasting. Laravel Echo (client-side) subscribes to channels and dispatches Livewire events when broadcasts arrive. All broadcasting is tenant-scoped using private channels.

## Server-Side Architecture

### Reverb Configuration

`config/reverb.php`:

| Setting | Value | Source |
|---------|-------|--------|
| Host | `0.0.0.0` | `REVERB_HOST` |
| Port | `8080` | `REVERB_PORT` |
| Scheme | `http` | `REVERB_SCHEME` |
| App ID | Auto-generated | `REVERB_APP_ID` |
| App Key | Auto-generated | `REVERB_APP_KEY` |
| App Secret | Auto-generated | `REVERB_APP_SECRET` |
| Max Request Size | `10000` KB | Default |
| Allowed Origins | `*` | Development default |

Reverb credentials are generated automatically by the `entrypoint.sh` script on first boot and written to `.env`.

### Process Management

Reverb runs as a persistent process under Supervisor:

```ini
[program:reverb]
command=php /var/www/html/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
```

### Network Path

```
Browser  -->  Nginx (:80)  -->  /app/*  -->  Reverb (:8080)
```

Nginx proxies WebSocket connections at the `/app/*` path to Reverb. The proxy configuration includes:

- `proxy_http_version 1.1`
- `Upgrade` and `Connection` headers for WebSocket handshake.
- `proxy_read_timeout 3600s` for long-lived connections.

## Broadcast Events

### Event Catalog

| Event | Channel | Payload | Trigger |
|-------|---------|---------|---------|
| `UserCreated` | `tenant.{tenantId}` | `userId`, `tenantId` | User creation in AccessControl module |
| `UserUpdated` | `tenant.{tenantId}` | `userId`, `tenantId` | User update in AccessControl module |
| `RoleUpdated` | `tenant.{tenantId}` | `roleId`, `tenantId` | Role create/update/delete in AccessControl module |
| `PermissionUpdated` | `tenant.{tenantId}` | `permissionId`, `tenantId` | Permission create/update/delete in AccessControl module |

All events implement `ShouldBroadcastNow`, meaning they are dispatched immediately without going through the queue. This ensures real-time delivery without queue latency.

### Event Structure

```php
class UserCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $userId,
        public string $tenantId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId),
        ];
    }
}
```

### Channel Authorization

Private channels require authorization. The `channels.php` file defines authorization callbacks:

```php
Broadcast::channel('tenant.{tenantId}', function (User $user, string $tenantId) {
    return $user->tenant_id === $tenantId;
});
```

Only users belonging to the specified tenant can subscribe to its broadcast channel.

## Client-Side Architecture

### Echo Configuration

`resources/js/app.js` initializes Echo with the Reverb driver:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

### Environment Variables

| Variable | Default | Purpose |
|----------|---------|---------|
| `VITE_REVERB_APP_KEY` | Auto-generated | Echo authentication key |
| `VITE_REVERB_HOST` | `localhost` | WebSocket host for the browser |
| `VITE_REVERB_PORT` | `8888` | WebSocket port (via Nginx proxy) |
| `VITE_REVERB_SCHEME` | `http` | Connection protocol |

Note: The browser connects to `localhost:8888` (Nginx), which proxies to Reverb on port 8080 internally.

### Channel Subscription

On `livewire:init`, the script reads the tenant ID from `window.wevetelTenantId` (injected by the sidebar layout) and subscribes to the tenant's private channel:

```javascript
document.addEventListener('livewire:init', () => {
    const tenantId = window.wevetelTenantId;
    if (tenantId && window.Echo) {
        window.Echo.private(`tenant.${tenantId}`)
            .listen('.UserCreated', () => {
                Livewire.dispatch('refreshComponent');
            })
            .listen('.UserUpdated', () => {
                Livewire.dispatch('refreshComponent');
            })
            .listen('.RoleUpdated', () => {
                Livewire.dispatch('refreshComponent');
            })
            .listen('.PermissionUpdated', () => {
                Livewire.dispatch('refreshComponent');
            });
    }
});
```

### Livewire Integration

When a broadcast event arrives, Echo dispatches a `refreshComponent` Livewire event. Livewire components listening for this event re-render their data:

```php
#[On('refreshComponent')]
public function refreshComponent(): void
{
    // Component re-renders automatically
}
```

This pattern enables real-time updates across browser tabs and users within the same tenant.

## Data Flow

```
User Action (Browser A)
    |
    v
Livewire Component (Server)
    |
    v
Model Created/Updated
    |
    v
Event Dispatched (ShouldBroadcastNow)
    |
    v
Reverb WebSocket Server
    |
    v
Private Channel: tenant.{tenantId}
    |
    v
Echo (Browser B)
    |
    v
Livewire.dispatch('refreshComponent')
    |
    v
Component Re-renders (Browser B)
```

## Adding New Broadcast Events

### 1. Create the Event

```bash
php artisan make:event TenantUpdated
```

Implement `ShouldBroadcastNow` and broadcast on the tenant channel:

```php
class TenantUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $tenantId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('tenant.' . $this->tenantId)];
    }
}
```

### 2. Dispatch the Event

Fire the event after the model operation:

```php
TenantUpdated::dispatch($tenant->id);
```

### 3. Subscribe on the Client

Add a listener in `app.js`:

```javascript
.listen('.TenantUpdated', () => {
    Livewire.dispatch('refreshComponent');
})
```

### 4. Handle in Livewire

Add the `#[On('refreshComponent')]` attribute to any component that should refresh.

## Troubleshooting

### WebSocket Connection Fails

1. Verify Reverb is running: `supervisorctl status reverb`.
2. Check Nginx proxy logs for WebSocket errors.
3. Verify `VITE_REVERB_HOST` and `VITE_REVERB_PORT` in `.env`.
4. Check browser console for connection errors.

### Events Not Received

1. Verify the event implements `ShouldBroadcastNow`.
2. Check channel authorization in `routes/channels.php`.
3. Confirm `window.wevetelTenantId` is set in the sidebar layout.
4. Verify Echo is initialized before the `livewire:init` event.

### Channel Authorization Denied

1. Ensure the authenticated user's `tenant_id` matches the channel's tenant ID.
2. Check Telescope's "Requests" section for failed channel authorization attempts.
