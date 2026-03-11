# Testing Strategy

## Overview

The application uses Pest 4, built on top of PHPUnit 12, for all automated testing. Tests are organized as feature tests covering authentication flows, settings management, API endpoints, dashboard access, tenancy, tenant-scoped user access, and module-level CRUD operations. Static analysis is performed by PHPStan at level 5 with Larastan. Code style is enforced by Laravel Pint.

## Test Framework

| Tool | Version | Purpose |
|------|---------|---------|
| Pest | 4 | Test framework (BDD-style syntax) |
| PHPUnit | 12 | Underlying test runner |
| PHPStan | (via Larastan 3) | Static analysis at level 5 |
| Pint | 1 | Code style enforcement |

## Test Suite Structure

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── AuthenticationTest.php
│   │   ├── EmailVerificationTest.php
│   │   ├── PasswordConfirmationTest.php
│   │   ├── PasswordResetTest.php
│   │   ├── PasswordUpdateTest.php
│   │   └── RegistrationTest.php
│   ├── Settings/
│   │   ├── PasswordUpdateTest.php
│   │   ├── ProfileUpdateTest.php
│   │   └── DeleteAccountTest.php
│   ├── Api/
│   │   └── TokenControllerTest.php
│   ├── DashboardTest.php
│   ├── TenancyTest.php
│   └── TenantUserAccessTest.php
├── Pest.php
└── TestCase.php
```

Module tests reside within their respective module directories:

```
Modules/
├── AccessControl/
│   └── tests/
│       └── Feature/
│           ├── PermissionsTest.php
│           ├── RolesTest.php
│           └── UsersTest.php
└── OrganisationSetup/
    └── tests/
        └── Feature/
            └── TenantsTest.php
```

## Test Configuration

### `phpunit.xml`

```xml
<env name="APP_ENV" value="testing"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="BCRYPT_ROUNDS" value="4"/>
<env name="CACHE_STORE" value="array"/>
<env name="MAIL_MAILER" value="array"/>
<env name="PULSE_ENABLED" value="false"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="TELESCOPE_ENABLED" value="false"/>
```

All tests run against an in-memory SQLite database. External services (cache, mail, queue, session) use array drivers.

### `tests/Pest.php`

```php
uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class, RefreshDatabase::class)->in('../Modules/*/tests/Feature');
```

All feature tests automatically use `RefreshDatabase`, which runs migrations and rolls back after each test.

## Test Categories

### Authentication Tests

| Test File | Coverage |
|-----------|----------|
| `AuthenticationTest` | Login screen rendering, successful login, failed login with invalid password |
| `RegistrationTest` | Registration screen rendering, successful registration |
| `PasswordResetTest` | Reset link screen rendering, reset link request, password reset |
| `PasswordConfirmationTest` | Confirmation screen rendering, valid confirmation, invalid confirmation |
| `PasswordUpdateTest` | Password update with correct current password, rejection with wrong password |
| `EmailVerificationTest` | Verification notice screen, email verification via signed URL, re-verification prevention |

### Settings Tests

| Test File | Coverage |
|-----------|----------|
| `ProfileUpdateTest` | Profile page rendering, Livewire name/email update, email verification reset on change |
| `PasswordUpdateTest` | Password update via Livewire component with validation |
| `DeleteAccountTest` | Account deletion with correct password, rejection with wrong password |

### API Tests

| Test File | Coverage |
|-----------|----------|
| `TokenControllerTest` | Token creation, token listing, token revocation, validation rules, correct token count |

### Dashboard Tests

| Test File | Coverage |
|-----------|----------|
| `DashboardTest` | Guest redirect to login, authenticated user access |

### Tenancy Tests

| Test File | Coverage |
|-----------|----------|
| `TenancyTest` | Tenant creation, tenant identification by subdomain, tenant identification by path prefix, data isolation between tenants |
| `TenantUserAccessTest` | User assignment to correct tenant, cross-tenant access denial, role/permission scoping per tenant |

### Module Tests

| Test File | Coverage |
|-----------|----------|
| `UsersTest` | User CRUD operations, filtering, search, pagination, role assignment, soft delete, restore, force delete, import, export |
| `RolesTest` | Role CRUD, permission assignment, guard name handling, soft delete, restore |
| `PermissionsTest` | Permission CRUD, guard name handling, soft delete, restore |
| `TenantsTest` | Tenant CRUD, domain management, mode validation, soft delete, restore |

## Writing Tests

### Creating a New Test

```bash
php artisan make:test --pest Feature/ExampleTest
```

For unit tests:

```bash
php artisan make:test --pest --unit Unit/ExampleTest
```

### Pest Syntax Conventions

```php
it('displays the dashboard for authenticated users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
});
```

### Testing Livewire Components

```php
use Livewire\Livewire;

it('updates the profile name', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('name', 'New Name')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('New Name');
});
```

### Testing with Factories

Always use model factories for test data creation:

```php
$user = User::factory()->create();
$tenant = Tenant::factory()->create(['mode' => 'subdomain']);
```

### Testing Tenant-Scoped Features

```php
it('scopes permissions to the current tenant', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    tenancy()->initialize($tenant);

    $this->actingAs($user);

    // Test within tenant context
});
```

## Running Tests

### All Tests

```bash
php artisan test --compact
```

### Specific File

```bash
php artisan test --compact tests/Feature/Auth/AuthenticationTest.php
```

### Filtered by Name

```bash
php artisan test --compact --filter="displays the dashboard"
```

### Module Tests

```bash
php artisan test --compact Modules/AccessControl/tests/Feature/UsersTest.php
```

### With Coverage

```bash
php artisan test --coverage
```

## Static Analysis

### PHPStan Configuration

`phpstan.neon`:

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 5
    paths:
        - app/
    tmpDir: storage/phpstan
```

### Running PHPStan

```bash
vendor/bin/phpstan analyse
```

## Code Style

### Pint Configuration

`pint.json`:

```json
{
    "preset": "laravel"
}
```

### Running Pint

```bash
vendor/bin/pint --dirty --format agent
```

## Testing Best Practices

1. **Feature tests over unit tests**: Most application behavior should be tested through feature tests that exercise the full HTTP stack.
2. **Use factories**: Never construct model instances manually in tests. Leverage factory states for specialized scenarios.
3. **Use `RefreshDatabase`**: All feature tests automatically migrate and roll back via the `Pest.php` configuration.
4. **Test authorization**: Verify that unauthorized users receive 403 responses and that permission gates function correctly.
5. **Test tenant isolation**: When testing multi-tenant features, verify that data from one tenant is not accessible from another.
6. **Avoid testing framework internals**: Test application behavior, not Laravel or Livewire framework code.
7. **Keep tests independent**: Each test should set up its own state and not depend on execution order.
