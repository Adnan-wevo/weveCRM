<?php

/**
 * Tenant component tests – authoritative suite lives in
 * tests/Feature/OrganisationSetup/TenantsTest.php.
 *
 * The Tenants CRUD components were moved from App\Livewire\AccessControl\Tenants
 * to Modules\OrganisationSetup\Livewire\Tenants. These tests mirror the OrganisationSetup
 * suite and point to the correct namespace so this file continues to pass.
 */

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;
use Modules\OrganisationSetup\Livewire\Tenants\CreateModal;
use Modules\OrganisationSetup\Livewire\Tenants\DeleteModal;
use Modules\OrganisationSetup\Livewire\Tenants\EditModal;
use Modules\OrganisationSetup\Livewire\Tenants\Index;

beforeEach(function () {
    $admin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($admin);
    $this->actingAs($user);
});

// ── Index ─────────────────────────────────────────────────────────────

it('ac: renders the tenants index component', function () {
    Livewire::test(Index::class)
        ->assertOk();
});

it('ac: lists existing tenants', function () {
    Tenant::create(['id' => 'acme', 'name' => 'Acme Corp']);

    Livewire::test(Index::class)
        ->assertSee('acme')
        ->assertSee('Acme Corp');
});

it('ac: filters tenants by search term', function () {
    Tenant::create(['id' => 'acme', 'name' => 'Acme Corp']);
    Tenant::create(['id' => 'beta', 'name' => 'Beta Inc']);

    Livewire::test(Index::class)
        ->set('search', 'acme')
        ->assertSee('Acme Corp')
        ->assertDontSee('Beta Inc');
});

it('ac: refreshes when tenant-saved event is dispatched', function () {
    $component = Livewire::test(Index::class);

    Tenant::create(['id' => 'fresh', 'name' => 'Fresh Co']);

    $component->dispatch('tenant-saved')
        ->assertSee('fresh');
});

// ── CreateModal ───────────────────────────────────────────────────────

it('ac: opens create modal on open-create-tenant event', function () {
    Livewire::test(CreateModal::class)
        ->assertSet('show', false)
        ->dispatch('open-create-tenant')
        ->assertSet('show', true);
});

it('ac: creates a tenant with an auto-generated id', function () {
    Livewire::test(CreateModal::class)
        ->dispatch('open-create-tenant')
        ->set('formName', 'New Organisation')
        ->call('create')
        ->assertDispatched('tenant-saved');

    expect(Tenant::withoutGlobalScopes()->where('data->name', 'New Organisation')->exists())->toBeTrue();
});

it('ac: creates a tenant with a custom id', function () {
    Livewire::test(CreateModal::class)
        ->dispatch('open-create-tenant')
        ->set('formId', 'my-org')
        ->set('formName', 'My Organisation')
        ->call('create')
        ->assertDispatched('tenant-saved');

    expect(Tenant::withoutGlobalScopes()->find('my-org'))->not->toBeNull()
        ->and(Tenant::withoutGlobalScopes()->find('my-org')->name)->toBe('My Organisation');
});

it('ac: validates that name is required on create', function () {
    Livewire::test(CreateModal::class)
        ->dispatch('open-create-tenant')
        ->set('formName', '')
        ->call('create')
        ->assertHasErrors(['formName' => 'required']);
});

it('ac: validates that custom id is unique', function () {
    Tenant::create(['id' => 'taken', 'name' => 'Taken Org']);

    Livewire::test(CreateModal::class)
        ->dispatch('open-create-tenant')
        ->set('formId', 'taken')
        ->set('formName', 'Another Org')
        ->call('create')
        ->assertHasErrors(['formId']);
});

it('ac: validates that custom id only allows lowercase alphanumeric characters', function () {
    Livewire::test(CreateModal::class)
        ->dispatch('open-create-tenant')
        ->set('formId', 'UPPER_CASE!')
        ->set('formName', 'Org')
        ->call('create')
        ->assertHasErrors(['formId']);
});

// ── EditModal ─────────────────────────────────────────────────────────

it('ac: opens edit modal with tenant data on open-edit-tenant event', function () {
    $tenant = Tenant::create(['id' => 'edit-me', 'name' => 'Old Name']);

    Livewire::test(EditModal::class)
        ->dispatch('open-edit-tenant', id: $tenant->getTenantKey())
        ->assertSet('tenantId', 'edit-me')
        ->assertSet('formName', 'Old Name')
        ->assertSet('show', true);
});

it('ac: updates a tenant name', function () {
    $tenant = Tenant::create(['id' => 'update-me', 'name' => 'Old Name']);

    Livewire::test(EditModal::class)
        ->dispatch('open-edit-tenant', id: $tenant->getTenantKey())
        ->set('formName', 'Updated Name')
        ->call('update')
        ->assertDispatched('tenant-saved');

    expect(Tenant::withoutGlobalScopes()->find('update-me')->name)->toBe('Updated Name');
});

it('ac: validates name is required on edit', function () {
    $tenant = Tenant::create(['id' => 'val-edit', 'name' => 'Name']);

    Livewire::test(EditModal::class)
        ->dispatch('open-edit-tenant', id: $tenant->getTenantKey())
        ->set('formName', '')
        ->call('update')
        ->assertHasErrors(['formName' => 'required']);
});

// ── DeleteModal ───────────────────────────────────────────────────────

it('ac: opens delete modal on open-delete-tenant event', function () {
    $tenant = Tenant::create(['id' => 'del-me', 'name' => 'Delete Me']);

    Livewire::test(DeleteModal::class)
        ->dispatch('open-delete-tenant', id: $tenant->getTenantKey())
        ->assertSet('show', true);
});

it('ac: soft-deletes a tenant on delete', function () {
    $tenant = Tenant::create(['id' => 'gone', 'name' => 'Gone Corp']);

    Livewire::test(DeleteModal::class)
        ->dispatch('open-delete-tenant', id: $tenant->getTenantKey())
        ->call('delete')
        ->assertDispatched('tenant-saved');

    expect(Tenant::find('gone'))->toBeNull()
        ->and(Tenant::withTrashed()->find('gone'))->not->toBeNull();
});
