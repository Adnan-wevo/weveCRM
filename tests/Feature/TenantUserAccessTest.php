<?php

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;

// ── EnsureCentralAccess middleware ─────────────────────────────────────────────

it('central user without tenant membership can access dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertOk();
});

it('super-admin can access central dashboard even with tenant membership', function () {
    $admin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($admin);

    $tenant = Tenant::create(['id' => 'restricted', 'name' => 'Restricted Corp']);
    $user->tenants()->attach($tenant->id);

    $this->actingAs($user);

    $this->get(route('dashboard'))->assertOk();
});

it('tenant user is redirected from central dashboard to their tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create(['id' => 'my-org', 'name' => 'My Org']);
    $user->tenants()->attach($tenant->id);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertRedirect(route('tenant.dashboard', ['tenant' => 'my-org']));
});

it('tenant user is redirected from central access-control to their tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create(['id' => 'blockme', 'name' => 'Block Me']);
    $user->tenants()->attach($tenant->id);

    $this->actingAs($user);

    $this->get(route('access-control.users'))
        ->assertRedirect(route('tenant.dashboard', ['tenant' => 'blockme']));
});

it('tenant user is redirected from central settings to their tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create(['id' => 'blockset', 'name' => 'Block Settings']);
    $user->tenants()->attach($tenant->id);

    $this->actingAs($user);

    $this->get(route('profile.edit'))
        ->assertRedirect(route('tenant.dashboard', ['tenant' => 'blockset']));
});

// ── User–Tenant relationship ───────────────────────────────────────────────────

it('can attach users to a tenant via the pivot table', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create(['id' => 'pivot-test', 'name' => 'Pivot Corp']);

    $user->tenants()->attach($tenant->id);

    expect($user->tenants()->first()?->id)->toBe('pivot-test')
        ->and($tenant->users()->first()?->id)->toBe($user->id);
});

// ── LoginResponse ─────────────────────────────────────────────────────────────

it('central login redirects tenant user to their tenant dashboard', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);
    $tenant = Tenant::create(['id' => 'logintest', 'name' => 'Login Test']);
    $user->tenants()->attach($tenant->id);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', ['tenant' => 'logintest']));
});

it('central login keeps central users on central dashboard', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));
});

// ── Tenant register session linking ───────────────────────────────────────────

it('registering with session registering_tenant_id links the new user to that tenant', function () {
    $tenant = Tenant::create(['id' => 'reg-org', 'name' => 'Reg Org']);

    // Simulate visiting the tenant register page which stores the tenant ID in session.
    $this->withSession(['registering_tenant_id' => $tenant->id]);

    $this->post(route('register'), [
        'name' => 'New Tenant User',
        'email' => 'newtenantuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $user = \App\Models\User::where('email', 'newtenantuser@example.com')->firstOrFail();

    expect($user->tenants()->where('id', $tenant->id)->exists())->toBeTrue();
});

it('session registering_tenant_id is consumed after registration', function () {
    $tenant = Tenant::create(['id' => 'reg-org2', 'name' => 'Reg Org 2']);

    $this->withSession(['registering_tenant_id' => $tenant->id]);

    $response = $this->post(route('register'), [
        'name' => 'Tenant User One',
        'email' => 'tenantone@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    // Session key should have been pulled (removed) by CreateNewUser.
    $response->assertSessionMissing('registering_tenant_id');
});

it('registering without session tenant id creates a central user', function () {
    $this->post(route('register'), [
        'name' => 'Central User',
        'email' => 'centraluser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $user = \App\Models\User::where('email', 'centraluser@example.com')->firstOrFail();

    expect($user->tenants()->exists())->toBeFalse();
});

// ── EnsureTenantAccess middleware ─────────────────────────────────────────────

it('super-admin is redirected away from tenant dashboard to central dashboard', function () {
    $admin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($admin);

    $tenant = Tenant::create(['id' => 'super-block', 'name' => 'Super Block']);
    $centralDomain = config('tenancy.central_domains')[0] ?? 'localhost';
    $tenant->domains()->create(['domain' => "super-block.{$centralDomain}"]);

    $this->actingAs($user);

    $this->get(route('tenant.dashboard', ['tenant' => $tenant->id]))
        ->assertRedirect(route('dashboard'));
});

it('super-admin is redirected away from tenant settings to central dashboard', function () {
    $admin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($admin);

    $tenant = Tenant::create(['id' => 'super-settings', 'name' => 'Super Settings']);
    $centralDomain = config('tenancy.central_domains')[0] ?? 'localhost';
    $tenant->domains()->create(['domain' => "super-settings.{$centralDomain}"]);

    $this->actingAs($user);

    $this->get(route('tenant.profile.edit', ['tenant' => $tenant->id]))
        ->assertRedirect(route('dashboard'));
});

it('tenant user of a different tenant is redirected to their own tenant dashboard', function () {
    $userTenant = Tenant::create(['id' => 'user-home', 'name' => 'User Home']);
    $otherTenant = Tenant::create(['id' => 'other-org', 'name' => 'Other Org']);
    $centralDomain = config('tenancy.central_domains')[0] ?? 'localhost';
    $userTenant->domains()->create(['domain' => "user-home.{$centralDomain}"]);
    $otherTenant->domains()->create(['domain' => "other-org.{$centralDomain}"]);

    $user = User::factory()->create();
    $user->tenants()->attach($userTenant->id);

    $this->actingAs($user);

    $this->get(route('tenant.dashboard', ['tenant' => $otherTenant->id]))
        ->assertRedirect(route('tenant.dashboard', ['tenant' => $userTenant->id]));
});

it('tenant user can access their own tenant dashboard', function () {
    $tenant = Tenant::create(['id' => 'own-tenant', 'name' => 'Own Tenant']);
    $centralDomain = config('tenancy.central_domains')[0] ?? 'localhost';
    $tenant->domains()->create(['domain' => "own-tenant.{$centralDomain}"]);

    $user = User::factory()->create();
    $user->tenants()->attach($tenant->id);

    $this->actingAs($user);

    $this->get(route('tenant.dashboard', ['tenant' => $tenant->id]))
        ->assertOk();
});
