# Frontend Architecture

## Overview

The frontend is a server-rendered application using Livewire 4 for reactive components, Flux UI Free for pre-built UI components, Tailwind CSS 4 for styling, and Alpine.js for client-side interactivity. There is no JavaScript SPA framework. Vite 7 handles asset bundling and hot module replacement during development.

## Technology Stack

| Technology | Version | Role |
|------------|---------|------|
| Livewire | 4 | Server-rendered reactive components |
| Flux UI Free | 2 | Pre-built accessible component library |
| Tailwind CSS | 4 | Utility-first CSS framework |
| Alpine.js | (bundled with Livewire) | Client-side interactivity |
| Vite | 7 | Asset bundling and HMR |
| Laravel Echo | 2 | WebSocket client for broadcasting |
| Pusher.js | 8 | WebSocket protocol library (used by Echo with Reverb) |

## Vite Configuration

```javascript
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: process.env.VITE_HMR_HOST || 'localhost',
            port: parseInt(process.env.VITE_HMR_PORT || '5173'),
        },
    },
});
```

**Entry Points**:

- `resources/css/app.css` -- Tailwind CSS with Wevetel theme
- `resources/js/app.js` -- Echo/Reverb WebSocket initialization

**Docker Integration**: The Vite dev server runs in a separate `vite` container. Nginx proxies `/@vite/*` requests to the Vite container for HMR.

## Tailwind CSS Theme

The application defines a custom Wevetel color palette in `resources/css/app.css`:

| Token | Color | Usage |
|-------|-------|-------|
| `--color-wevetel-primary` | `#1b96c6` | Primary brand color |
| `--color-wevetel-sidebar` | `#0c4d65` | Sidebar background |
| `--color-wevetel-sidebar-hover` | `#0e5f7d` | Sidebar hover state |
| `--color-wevetel-sidebar-active` | `#1080aa` | Active sidebar item |

Flux UI's accent color is overridden via `--color-accent`:

```css
:root {
    --color-accent: var(--color-wevetel-primary);
}
```

Custom sidebar styling uses the `.wevetel-sidebar` class with transition effects on hover and active states.

Dark mode overrides are provided for all custom properties.

## Layout System

### Application Layout (`resources/views/layouts/app.blade.php`)

The main application layout wraps the sidebar layout with:

- Breadcrumb header area with a live clock component.
- Main content area where Livewire components render.
- Wevetel-branded footer.

### Sidebar Layout (`resources/views/layouts/app/sidebar.blade.php`)

Full sidebar navigation with approximately 155 lines:

- **Dashboard** link (always visible to authenticated users).
- **Access Control** group (conditional via `@canany`):
  - Users
  - Roles
  - Permissions
- **Organisation Setup** group (conditional via `@can`):
  - Tenants

The sidebar is tenant-aware:

- Detects the current tenancy mode.
- Shows a tenant badge when in tenant context.
- Generates correct route URLs using `ResolveTenantRouteParam`.
- Exposes `window.wevetelTenantId` for JavaScript consumption.

### Auth Layouts

Three authentication layout variants:

| Layout | File | Design |
|--------|------|--------|
| Simple | `layouts/auth/simple.blade.php` | Minimal centered form |
| Card | `layouts/auth/card.blade.php` | Card with rounded border |
| Split | `layouts/auth/split.blade.php` | Split-screen with inspirational quote |

## Livewire Components

### Full-Page Components

Full-page Livewire components mount as the main content of a layout. They use the `#[Layout('layouts.app')]` or `#[Layout('layouts.auth')]` attribute.

| Component | Layout | Route |
|-----------|--------|-------|
| `MagicLogin` | auth | `/magic-login/request` |
| `Profile` | app (settings) | `/settings/profile` |
| `Password` | app (settings) | `/settings/password` |
| `Appearance` | app (settings) | `/settings/appearance` |
| `TwoFactor` | app (settings) | `/settings/two-factor` |

Module index components (Users, Roles, Permissions, Tenants) are rendered as Livewire views within Blade templates, not as full-page components.

### Modal Components

All CRUD operations use modal components for create, show, edit, delete, and specialized operations. Modals are rendered within the index page and toggled via Livewire events.

Common modal features:

- **Validation**: Server-side validation with inline error display.
- **Record Locking**: Edit modals check and acquire locks before opening.
- **Audit History**: History modals display the full audit trail with event type badges and old/new value diffs.
- **Import/Export**: ImportExport modals handle file upload, preview, validation, progress polling, and download.

### Component Communication

Components communicate via Livewire events:

| Event | Direction | Purpose |
|-------|-----------|---------|
| `openCreateModal` | Index -> CreateModal | Open create form |
| `openShowModal` | Index -> ShowModal | Open detail view |
| `openEditModal` | Index -> EditModal | Open edit form, acquire lock |
| `openDeleteModal` | Index -> DeleteModal | Open delete confirmation |
| `refreshComponent` | Broadcast -> Index | Refresh data table |
| `password-updated` | Password -> Toast | Show success notification |

## Flux UI Components

The application uses Flux UI Free components extensively. Key components used:

| Component | Usage |
|-----------|-------|
| `flux:input` | Text inputs, email fields, password fields |
| `flux:button` | Action buttons with variants (primary, danger, ghost) |
| `flux:modal` | Dialog overlays for CRUD operations |
| `flux:dropdown` | Action menus on table rows |
| `flux:badge` | Status indicators (roles, permissions, verification) |
| `flux:checkbox` | Multi-select checkboxes (role/permission assignment) |
| `flux:radio.group` | Theme selection (appearance settings) |
| `flux:otp` | 6-digit OTP input for 2FA challenge |
| `flux:tabs` | Active/Trash tab switching |
| `flux:table` | Data table structure |
| `flux:brand` | Logo component |

## Alpine.js Integration

Alpine.js is used for client-side interactivity that does not require server round-trips:

### Toast Notifications (`resources/views/components/partials/toast.blade.php`)

A 105-line Alpine.js component manages a toast notification stack:

- Supports 4 types: success, error, warning, info.
- Auto-dismisses after a configurable timeout.
- Stacks multiple notifications.
- Animated entrance and exit transitions.
- Listens to `toast` events dispatched by Livewire components.

### Theme Switcher

The appearance settings page uses Alpine.js to toggle between light, dark, and system theme modes.

### Live Clock

The sidebar header includes an Alpine.js-powered clock that updates every second.

## Broadcasting Client

`resources/js/app.js` initializes Laravel Echo with the Reverb WebSocket driver:

```javascript
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
});
```

On `livewire:init`, the script subscribes to tenant-scoped broadcast channels and dispatches `refreshComponent` events when record change events arrive.

## Blade Components

### Application Components

| Component | File | Purpose |
|-----------|------|---------|
| `app-logo` | `components/app-logo.blade.php` | Renders Wevetel logo (PNG in sidebar, Flux brand elsewhere) |
| `desktop-user-menu` | `components/desktop-user-menu.blade.php` | Profile dropdown with Settings and Logout links |
| `settings/layout` | `components/settings/layout.blade.php` | Settings page navigation sidebar |
| `auth-header` | `components/auth-header.blade.php` | Centered title and description for auth pages |

### Partial Components

| Component | File | Purpose |
|-----------|------|---------|
| `partials/toast` | `components/partials/toast.blade.php` | Alpine.js toast notification system |
| `partials/head` | `components/partials/head.blade.php` | Document head with meta, favicon, fonts, Vite assets |

## Error Pages

Custom error pages with consistent Wevetel branding:

| Code | Purpose |
|------|---------|
| 403 | Forbidden |
| 404 | Not Found |
| 419 | Page Expired (CSRF) |
| 429 | Too Many Requests |
| 500 | Internal Server Error |
| 503 | Service Unavailable |
| tenant-not-found | Unknown Tenant ID |

All error pages use a dark teal gradient background with centered error messaging.

## Data Table Pattern

Module index views share a comprehensive data table implementation:

1. **Header**: Title, subtitle, action buttons (New, Import/Export, Sync).
2. **Active/Trash Tabs**: Toggle between active and soft-deleted records.
3. **Filter Panel**: Global search input, dynamic column filter rows with operator selection (20+ operators from Purity), per-page selector.
4. **Table**: Checkbox selection column, sortable column headers, data columns with contextual formatting, action dropdown per row.
5. **Pagination**: Showing X-Y of Z with page navigation.
6. **Modals**: Rendered alongside the table, toggled by Livewire events.

## Extending the Frontend

### Adding New Pages

1. Create a Blade view in the appropriate module's `resources/views/` directory.
2. Create Livewire components for reactive functionality.
3. Register routes with appropriate middleware.
4. Add navigation entries to the sidebar layout.
5. Protect with permission-based `@can` directives.

### Adding New Modals

1. Create a Livewire component extending the project's modal pattern.
2. Register event listeners for opening/closing.
3. Include the component in the parent index view.
4. Follow existing validation and error handling patterns.

### Styling Guidelines

- Use Tailwind CSS utility classes exclusively.
- Follow the Wevetel color palette defined in `app.css`.
- Use Flux UI components where available.
- Ensure dark mode compatibility by using Tailwind's `dark:` variants.
