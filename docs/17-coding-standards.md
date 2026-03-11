# Coding Standards

## Overview

The project enforces coding standards through automated tooling: Laravel Pint for code style formatting and PHPStan (via Larastan) for static analysis. CI pipelines validate both on every push and pull request.

## Code Style — Laravel Pint

### Configuration

`pint.json`:

```json
{
    "preset": "laravel"
}
```

The `laravel` preset follows Laravel's official code style, which is based on PSR-12 with additional opinionated rules.

### Running Pint

Format changed files:

```bash
vendor/bin/pint --dirty --format agent
```

Format all files:

```bash
vendor/bin/pint
```

Check without modifying (CI mode):

```bash
vendor/bin/pint --test
```

### Key Style Rules

| Rule | Convention |
|------|-----------|
| Indentation | 4 spaces |
| Line length | Soft limit 120 characters |
| Braces | Same line for classes and methods |
| Trailing commas | Required in multi-line arrays and parameter lists |
| Imports | One import per line, grouped and sorted alphabetically |
| Blank lines | One blank line between methods, two between class sections |
| String quotes | Single quotes for non-interpolated strings |

## Static Analysis — PHPStan / Larastan

### Configuration

`phpstan.neon`:

```neon
includes:
    - vendor/larastan/larastan/extension.neon
    - vendor/nesbot/carbon/extension.neon

parameters:
    paths:
        - app/
        - Modules/AccessControl/app/
        - Modules/OrganisationSetup/app/

    level: 5

    ignoreErrors:
        # Jobs intentionally store userId in constructor for serialization/context;
        # the property is written once and never read locally — that is by design.
        -
            identifier: property.onlyWritten
            path: app/Jobs/
        -
            identifier: property.onlyWritten
            path: Modules/OrganisationSetup/app/Jobs/
```

> **Important:** PHPStan does not support glob patterns in `paths`. When a new module is added, its `app/` directory must be explicitly listed here.

### Analysis Level

Level 5 checks include:

- Unknown classes, functions, and methods.
- Wrong number of arguments passed to functions and methods.
- Wrong variable types in assignments and comparisons.
- Missing return types.
- Dead code detection.
- Type checking for property access and method calls.

### Running PHPStan

```bash
vendor/bin/phpstan analyse
# or via Composer
composer phpstan
```

### Suppressing False Positives

Use inline `@phpstan-ignore` comments for known false positives. Always place the comment on the **line before** or **same line as** the offending call, and include the error identifier.

```php
// Socialite's concrete driver has with() but the contract does not declare it.
$driver = Socialite::driver('keycloak') // @phpstan-ignore method.notFound
    ->with(['prompt' => 'login'])
    ->redirect();

// withTrashed()->find() can return null; ?-> is intentionally defensive.
$this->recordName = $record?->name ?? $id; // @phpstan-ignore nullsafe.neverNull

// Stancl Tenancy provides domains() dynamically via HasDomains trait.
Tenant::with('domains')->find($id); // @phpstan-ignore larastan.relationExistence

// Spatie's create() returns the contract interface, not our concrete model.
return $role; // @phpstan-ignore return.type
```

### Typing Inside Closures

When a collection's generic type is broader than what the code expects (e.g., Spatie returns `Collection<Spatie\Permission\Models\Permission>` but the variable is typed as the base model), use an inline `@var` inside the closure body rather than a typed parameter — typed closure parameters are **contravariant** and will cause a different PHPStan error:

```php
// Correct — @var assertion inside the body
$role->permissions->filter(function ($p) {
    /** @var \App\Models\Permission $p */
    return $p->tenant_id === tenant('id');
});

// Incorrect — typed closure parameter causes argument.type error on the filter() call
$role->permissions->filter(fn (\App\Models\Permission $p) => $p->tenant_id === tenant('id'));
```

## PHP Conventions

### Type Declarations

All methods and functions must have explicit return type declarations and parameter type hints.

```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    // ...
}
```

### Constructor Property Promotion

Use PHP 8 constructor property promotion. Do not leave empty constructors with zero parameters.

```php
public function __construct(
    public GitHub $github,
    protected Logger $logger,
) {}
```

### Control Structures

Always use curly braces, including for single-line bodies.

```php
if ($condition) {
    return true;
}
```

### Enums

Enum keys use `TitleCase`:

```php
enum TenancyMode: string
{
    case Single = 'single';
    case Subdomain = 'subdomain';
    case Path = 'path';
}
```

### Comments

- Prefer PHPDoc blocks over inline comments.
- Do not use inline comments unless the logic is exceptionally complex.
- Add array shape type definitions in PHPDoc when appropriate.

```php
/**
 * @param  array{name: string, email: string, role?: string}  $data
 * @return User
 */
public function createUser(array $data): User
```

## Naming Conventions

### Files and Classes

| Type | Convention | Example |
|------|-----------|---------|
| Model | Singular PascalCase | `User`, `Tenant`, `Permission` |
| Controller | PascalCase + `Controller` | `TokenController`, `KeycloakController` |
| Livewire Component | PascalCase | `CreateUserModal`, `EditRoleModal` |
| Form Request | PascalCase + `Request` | `StoreTokenRequest` |
| Action | Descriptive PascalCase | `CreateNewUser`, `SyncRolesPermissionsToTenantAction` |
| Event | Past tense PascalCase | `UserCreated`, `RoleUpdated` |
| Job | PascalCase + `Job` suffix | `NotifyUserOfCompletedExport` |
| Middleware | PascalCase | `EnsureValidTenantSession` |
| Migration | Snake case with timestamp | `2024_01_01_000001_create_users_table` |
| Test | PascalCase + `Test` | `AuthenticationTest`, `UsersTest` |

### Variables and Methods

- Variables: `camelCase` with descriptive names.
- Methods: `camelCase` with verb-noun patterns.
- Boolean variables/methods: Prefix with `is`, `has`, `can`, `should`.

```php
$isRegisteredForDiscounts = true;  // Descriptive
$discount = false;                  // Avoid: ambiguous
```

### Routes

- Route URIs: `kebab-case`.
- Route names: `dot.separated.lowercase`.

```php
Route::get('/access-control/users', [...])->name('access-control.users.index');
```

## Laravel Conventions

### Eloquent Models

- Define `$fillable` arrays (no blanket `$guarded`).
- Define `casts()` method (not `$casts` property) for attribute casting.
- Use relationship methods with return type declarations.
- Apply `SoftDeletes` trait where appropriate.
- **Declare custom and inherited properties via `@property` PHPDoc** on the class so static analysis tools (PHPStan / Larastan) can resolve them. This applies to:
  - Dynamic JSON-column attributes (e.g., `$name` on `Tenant`).
  - Extra columns added to Spatie model subclasses (e.g., `$tenant_id`, `$is_synced` on `Permission`/`Role`).
  - Inherited nullable timestamps such as `$deleted_at` when using `SoftDeletes`.

```php
/**
 * @property string|null $name
 * @property \Carbon\Carbon|null $deleted_at
 */
class Tenant extends BaseTenant implements AuditableContract
{
    // ...
}

/**
 * @property bool $is_synced
 * @property string|null $tenant_id
 * @property \Carbon\Carbon|null $deleted_at
 */
class Permission extends SpatiePermission implements AuditableContract
{
    // ...
}
```

```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}

public function tenant(): BelongsTo
{
    return $this->belongsTo(Tenant::class);
}
```

### Configuration Access

- Use `config('key')` in application code — **never call `env()` outside of a config file**.
- Use `env('KEY')` only inside `config/*.php` files.
- When a new third-party service needs credentials, add them to the appropriate `config/services.php` entry rather than reading `env()` directly in the command or class.

```php
// config/services.php
'keycloak' => [
    'admin_user' => env('KEYCLOAK_ADMIN_USER', 'admin'),
    'admin_password' => env('KEYCLOAK_ADMIN_PASSWORD', 'admin'),
],

// Correct — reads from config cache
$user = config('services.keycloak.admin_user');

// Incorrect — bypasses config cache, PHPStan reports this
$user = env('KEYCLOAK_ADMIN_USER');
```

### Route Definitions

- Use named routes exclusively.
- Group routes with shared middleware.
- Use route model binding where applicable.

### Migrations

- Use descriptive column names.
- Include appropriate indexes.
- Add foreign key constraints.
- Always define both `up()` and `down()` methods.

## Livewire Conventions

### Component Structure

1. Traits and imports at the top.
2. Public properties (bound to the view).
3. Protected/private properties.
4. `mount()` method for initialization.
5. Action methods (called from the view).
6. Computed properties.
7. `render()` method at the bottom.

### Property Naming

- Form field properties match the database column names.
- Boolean toggles use `show` prefix: `$showModal`, `$showFilters`.
- IDs use descriptive suffixes: `$selectedUserId`, `$editingRoleId`.

### Event Naming

- Events use `camelCase` verb phrases.
- Prefixed with action context: `openCreateModal`, `refreshComponent`.

## Module Conventions

### Directory Structure

Every module follows the same directory structure as the core application:

```
Modules/ModuleName/
├── app/
│   ├── Http/Controllers/
│   ├── Livewire/
│   ├── Models/
│   ├── Exports/
│   ├── Imports/
│   ├── Events/
│   └── Jobs/
├── config/
├── database/
│   └── migrations/
├── resources/views/
├── routes/
│   └── web.php
├── tests/Feature/
├── composer.json
├── module.json
└── vite.config.js
```

### Module Registration

- Module status is tracked in `modules_statuses.json`.
- Module service providers extend `ModuleServiceProvider`.
- Livewire component namespaces are registered in `config/modules-livewire.php`.


