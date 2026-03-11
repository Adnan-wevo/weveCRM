import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Subscribe to broadcast channels scoped to the current context (tenant or central).
// Only users in the same context (same tenant or both central) receive the events;
// cross-tenant and central-vs-tenant toasts are suppressed.
document.addEventListener('livewire:init', () => {
    const tenantId = window.wevetelTenantId ?? null;
    const suffix   = tenantId ? `.${tenantId}` : '.central';

    window.Echo.channel(`access-control.users${suffix}`)
        .listen('.UserRecordChanged', () => {
            Livewire.dispatch('user-record-changed-broadcast');
        });

    window.Echo.channel(`access-control.roles${suffix}`)
        .listen('.RoleRecordChanged', () => {
            Livewire.dispatch('role-record-changed-broadcast');
        });

    window.Echo.channel(`access-control.permissions${suffix}`)
        .listen('.PermissionRecordChanged', () => {
            Livewire.dispatch('permission-record-changed-broadcast');
        });

    window.Echo.channel(`organisation-setup.tenants${suffix}`)
        .listen('.TenantRecordChanged', () => {
            Livewire.dispatch('tenant-record-changed-broadcast');
        });
});
