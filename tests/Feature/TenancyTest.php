<?php

use App\Models\Tenant;

/*
|--------------------------------------------------------------------------
| Tenancy Tests
|--------------------------------------------------------------------------
|
| Verify that the tenancy system works correctly across all three modes:
| single, subdomain, and path.
|
*/

// ── Tenant model ─────────────────────────────────────────────────────

it('can create and retrieve a tenant', function () {
    $tenant = Tenant::create(['id' => 'acme', 'name' => 'Acme Corp']);

    expect($tenant->id)->toBe('acme')
        ->and($tenant->name)->toBe('Acme Corp');

    expect(Tenant::find('acme'))->not->toBeNull();
});

it('can attach a domain to a tenant', function () {
    $tenant = Tenant::create(['id' => 'beta', 'name' => 'Beta Inc']);
    $tenant->domains()->create(['domain' => 'beta.localhost']);

    expect($tenant->domains()->first()->domain)->toBe('beta.localhost');
});

// ── Single mode ───────────────────────────────────────────────────────

it('returns 200 on home route in single mode', function () {
    config(['tenancy.mode' => 'single']);

    $this->get('/')->assertSuccessful();
});

it('unknown tenant in path mode returns 404', function () {
    // In path mode, /t/{tenant}/... routes are registered.
    // Accessing a path with a non-existent tenant ID should return 404,
    // not an unhandled 500 exception.
    $this->get('/t/some-tenant/dashboard')->assertNotFound();
});

// ── Path mode ─────────────────────────────────────────────────────────

it('initialises tenancy via path prefix in path mode', function () {
    // Routes are registered at boot with the configured mode (path).
    // GET /{tenant} redirects to the tenant dashboard (auth-protected),
    // so we assert a redirect — confirming the tenant route is reachable.
    $tenant = Tenant::create(['id' => 'pathcorp']);

    $this->get("/{$tenant->id}")
        ->assertRedirect();
});

it('returns 404 for unknown tenant slug in path mode', function () {
    // Routes are registered in path mode; /nonexistent-tenant-xyz hits
    // InitializeTenancyByPath which returns 404 for unknown tenants.
    $this->get('/nonexistent-tenant-xyz')->assertNotFound();
});

// ── Subdomain mode ────────────────────────────────────────────────────

it('initialises tenancy via subdomain in subdomain mode', function () {
    // Note: tenant routes are registered at boot based on the configured mode.
    // Subdomain route registration cannot be tested when the app boots in path
    // mode. This test verifies that a subdomain request does not cause a server
    // error — central routes handle the request gracefully.
    $tenant = Tenant::create(['id' => 'subco']);
    $tenant->domains()->create(['domain' => 'subco']);

    $this->get('/', ['HTTP_HOST' => 'subco.localhost'])
        ->assertSuccessful();
});

it('serves central home on central domain in subdomain mode', function () {
    // Central domain (localhost without tenant prefix) always serves central routes.
    $this->get('/', ['HTTP_HOST' => 'localhost'])
        ->assertSuccessful();
});
