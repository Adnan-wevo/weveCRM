<?php

use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;
use Modules\OrganisationSetup\Livewire\Tenants\CreateModal;
use Modules\OrganisationSetup\Livewire\Tenants\DeleteModal;
use Modules\OrganisationSetup\Livewire\Tenants\EditModal;
use Modules\OrganisationSetup\Livewire\Tenants\Index;
use Modules\OrganisationSetup\Livewire\Tenants\SyncMigrationsModal;

beforeEach(function () {
    $admin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($admin);
    $this->actingAs($user);
});

// ── Index ──────────────────────────────────────────────────────────────────────

it('renders the tenants index component', function () {
    Livewire::test(Index::class)
        ->assertOk();
});

it('lists existing tenants', function () {
    \App\Models\Tenant::create(['id' => 'acme', 'name' => 'Acme Corp']);

    Livewire::test(Index::class)
        ->assertSee('acme')
        ->assertSee('Acme Corp');
});

it('filters tenants by search term', function () {
    \App\Models\Tenant::create(['id' => 'acme', 'name' => 'Acme Corp']);
    \App\Models\Tenant::create(['id' => 'beta', 'name' => 'Beta Inc']);

    Livewire::test(Index::class)
        ->set('search', 'acme')
        ->assertSee('Acme Corp')
        ->assertDontSee('Beta Inc');
});

it('refreshes when tenant-saved event is dispatched', function () {
    $component = Livewire::test(Index::class);

    \App\Models\Tenant::create(['id' => 'fresh', 'name' => 'Fresh Co']);

    $component->dispatch('tenant-saved')
        ->assertSee('fresh');
});

// ── CreateModal ────────────────────────────────────────────────────────────────

it('opens create modal on open-create-tenant event', function () {
    Livewire::test(CreateModal::class)
        ->assertSet('show', false)
        ->dispatch('open-create-tenant')
        ->assertSet('show', true);
});

it('creates a tenant with an auto-generated id', function () {
    Livewire::test(CreateModal::class)
        ->dispatch('open-create-tenant')
        ->set('formName', 'New Organisation')
        ->call('create')
        ->assertDispatched('tenant-saved');

    expect(\App\Models\Tenant::withoutGlobalScopes()->where('data->name', 'New Organisation')->exists())->toBeTrue();
});

it('creates a tenant with a custom id', function () {
    Livewire::test(CreateModal::class)
        ->dispatch('open-create-tenant')
        ->set('formId', 'my-org')
        ->set('formName', 'My Organisation')
        ->call('create')
        ->assertDispatched('tenant-saved');

    expect(\App\Models\Tenant::withoutGlobalScopes()->find('my-org'))->not->toBeNull()
        ->and(\App\Models\Tenant::withoutGlobalScopes()->find('my-org')->name)->toBe('My Organisation');
});

it('validates that name is required on create', function () {
    Livewire::test(CreateModal::class)
        ->dispatch('open-create-tenant')
        ->set('formName', '')
        ->call('create')
        ->assertHasErrors(['formName' => 'required']);
});

it('validates that custom id is unique', function () {
    \App\Models\Tenant::create(['id' => 'taken', 'name' => 'Taken Org']);

    Livewire::test(CreateModal::class)
        ->dispatch('open-create-tenant')
        ->set('formId', 'taken')
        ->set('formName', 'Another Org')
        ->call('create')
        ->assertHasErrors(['formId']);
});

it('validates that custom id only allows lowercase alphanumeric hyphens underscores', function () {
    Livewire::test(CreateModal::class)
        ->dispatch('open-create-tenant')
        ->set('formId', 'UPPER_CASE!')
        ->set('formName', 'Org')
        ->call('create')
        ->assertHasErrors(['formId']);
});

// ── EditModal ──────────────────────────────────────────────────────────────────

it('opens edit modal with tenant data on open-edit-tenant event', function () {
    $tenant = \App\Models\Tenant::create(['id' => 'edit-me', 'name' => 'Old Name']);

    Livewire::test(EditModal::class)
        ->dispatch('open-edit-tenant', id: $tenant->getTenantKey())
        ->assertSet('tenantId', 'edit-me')
        ->assertSet('formName', 'Old Name')
        ->assertSet('show', true);
});

it('updates a tenant name', function () {
    $tenant = \App\Models\Tenant::create(['id' => 'update-me', 'name' => 'Old Name']);

    Livewire::test(EditModal::class)
        ->dispatch('open-edit-tenant', id: $tenant->getTenantKey())
        ->set('formName', 'Updated Name')
        ->call('update')
        ->assertDispatched('tenant-saved');

    expect(\App\Models\Tenant::withoutGlobalScopes()->find('update-me')->name)->toBe('Updated Name');
});

it('validates name is required on edit', function () {
    $tenant = \App\Models\Tenant::create(['id' => 'val-edit', 'name' => 'Name']);

    Livewire::test(EditModal::class)
        ->dispatch('open-edit-tenant', id: $tenant->getTenantKey())
        ->set('formName', '')
        ->call('update')
        ->assertHasErrors(['formName' => 'required']);
});

// ── DeleteModal ────────────────────────────────────────────────────────────────

it('opens delete modal on open-delete-tenant event', function () {
    $tenant = \App\Models\Tenant::create(['id' => 'del-me', 'name' => 'Delete Me']);

    Livewire::test(DeleteModal::class)
        ->dispatch('open-delete-tenant', id: $tenant->getTenantKey())
        ->assertSet('show', true);
});

it('soft-deletes a tenant on delete', function () {
    $tenant = \App\Models\Tenant::create(['id' => 'gone', 'name' => 'Gone Corp']);

    Livewire::test(DeleteModal::class)
        ->dispatch('open-delete-tenant', id: $tenant->getTenantKey())
        ->call('delete')
        ->assertDispatched('tenant-saved');

    expect(\App\Models\Tenant::find('gone'))->toBeNull()
        ->and(\App\Models\Tenant::withTrashed()->find('gone'))->not->toBeNull();
});

// ── SyncMigrationsModal ────────────────────────────────────────────────────────

it('opens sync migrations modal on open-sync-migrations event', function () {
    Livewire::test(SyncMigrationsModal::class)
        ->assertSet('show', false)
        ->dispatch('open-sync-migrations')
        ->assertSet('show', true)
        ->assertSet('status', 'idle');
});

it('defaults to withSeed and withPermissionSync enabled', function () {
    Livewire::test(SyncMigrationsModal::class)
        ->dispatch('open-sync-migrations')
        ->assertSet('withSeed', true)
        ->assertSet('withPermissionSync', true);
});

it('shows no tenants message when syncing with zero tenants', function () {
    Livewire::test(SyncMigrationsModal::class)
        ->dispatch('open-sync-migrations')
        ->call('sync')
        ->assertSet('status', 'done')
        ->assertSet('resultMessage', __('No tenants found.'));
});

it('closes modal and resets state', function () {
    Livewire::test(SyncMigrationsModal::class)
        ->dispatch('open-sync-migrations')
        ->assertSet('show', true)
        ->call('close')
        ->assertSet('show', false)
        ->assertSet('status', 'idle');
});

it('dispatches open-sync-migrations from index', function () {
    Livewire::test(Index::class)
        ->call('openSyncMigrations')
        ->assertDispatched('open-sync-migrations');
});
